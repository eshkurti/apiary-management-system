<?php

declare(strict_types=1);

namespace common\models;

use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * Treatment — veterinary treatment record forming the Bestandsbuch entry.
 * All fields map to EU Regulation 2019/6 Article 108(2) mandatory columns.
 *
 * On save: automatically calculates wartezeit_expiry from application_date
 * and withdrawal_days using PHP DateTime for correctness across month boundaries.
 *
 * After save: refreshes the colony's annual treatment compliance flags.
 *
 * @property int    $id
 * @property int    $colony_id
 * @property int    $apiary_stand_id
 * @property string $treatment_type
 * @property string $product_name
 * @property string $pharmaceutical_batch_number
 * @property string $quantity_per_colony
 * @property string $supplier_name
 * @property string $supplier_address
 * @property string|null $receipt_number
 * @property string|null $veterinarian
 * @property string $application_date
 * @property int    $colonies_treated_at_stand
 * @property int    $withdrawal_days
 * @property string $wartezeit_expiry
 * @property int    $treatment_duration_days
 * @property string $operator_name
 * @property string|null $notes
 * @property int    $created_at
 * @property int    $updated_at
 * @property int    $created_by
 */
class Treatment extends ActiveRecord
{
    public const TYPE_VARROA        = 'varroa';
    public const TYPE_TRACHEENMILBE = 'tracheenmilbe';
    public const TYPE_OTHER         = 'other';

    public static function tableName(): string
    {
        return '{{%treatment}}';
    }

    public function behaviors(): array
    {
        return [
            TimestampBehavior::class,
            [
                'class'              => BlameableBehavior::class,
                'updatedByAttribute' => false,
            ],
        ];
    }

    public function rules(): array
    {
        return [
            // Required for all treatment types.
            // colonies_treated_at_stand is NOT listed: it is calculated
            // automatically by the controller from the active colony count
            // at the selected stand and is not a form field.
            [[
                'colony_id', 'apiary_stand_id', 'treatment_type',
                'product_name', 'pharmaceutical_batch_number',
                'quantity_per_colony', 'supplier_name', 'supplier_address',
                'application_date',
                'treatment_duration_days', 'operator_name',
            ], 'required'],

            // withdrawal_days required for varroa and tracheenmilbe (AC-PM-04.6)
            [['withdrawal_days'], 'required', 'when' => function (self $model): bool {
                return in_array($model->treatment_type, [self::TYPE_VARROA, self::TYPE_TRACHEENMILBE], true);
            }, 'whenClient' => "function(attribute, value) {
                var type = $('#treatment-treatment_type').val();
                return type === 'varroa' || type === 'tracheenmilbe';
            }"],

            [['withdrawal_days'], 'default', 'value' => 0],
            [['withdrawal_days'], 'integer', 'min' => 0],

            [['colony_id', 'apiary_stand_id', 'colonies_treated_at_stand', 'treatment_duration_days'], 'integer'],
            [['colonies_treated_at_stand'], 'integer', 'min' => 1],
            [['treatment_duration_days'], 'integer', 'min' => 1],

            [['treatment_type'], 'in', 'range' => [self::TYPE_VARROA, self::TYPE_TRACHEENMILBE, self::TYPE_OTHER]],

            [['application_date'], 'date', 'format' => 'php:Y-m-d'],
            [['application_date'], 'compare',
                'compareValue' => date('Y-m-d'),
                'operator'     => '<=',
                'message'      => 'Application date cannot be in the future.'],

            [['product_name', 'operator_name'], 'string', 'max' => 150],
            [['pharmaceutical_batch_number'], 'string', 'max' => 100],
            [['quantity_per_colony'], 'string', 'max' => 50],
            [['supplier_name'], 'string', 'max' => 150],
            [['supplier_address'], 'string', 'max' => 255],
            [['receipt_number'], 'string', 'max' => 100],
            [['veterinarian'], 'string', 'max' => 255],
            [['notes'], 'string'],

            [['colony_id'], 'exist', 'targetClass' => Colony::class, 'targetAttribute' => 'id'],
            [['apiary_stand_id'], 'exist', 'targetClass' => ApiaryStand::class, 'targetAttribute' => 'id'],

            // The treatment's stand must match the colony's current stand.
            [['colony_id'], 'validateColonyStand'],
        ];
    }

    /**
     * Ensures the selected colony is actually assigned to the selected stand.
     * Guards against tampering with the dependent colony dropdown (Change 1).
     */
    public function validateColonyStand(string $attribute): void
    {
        if (empty($this->colony_id) || empty($this->apiary_stand_id)) {
            return;
        }
        $colony = Colony::findOne($this->colony_id);
        if ($colony !== null && (int) $colony->apiary_stand_id !== (int) $this->apiary_stand_id) {
            $this->addError(
                $attribute,
                'The selected colony is not assigned to the selected apiary stand.',
            );
        }
    }

    public function attributeLabels(): array
    {
        return [
            'colony_id'                   => 'Colony',
            'apiary_stand_id'             => 'Apiary Stand',
            'treatment_type'              => 'Treatment Type',
            'product_name'                => 'Medicinal Product Name',
            'pharmaceutical_batch_number' => 'Product Batch / Charge Number',
            'quantity_per_colony'         => 'Quantity per Colony',
            'supplier_name'               => 'Supplier Name',
            'supplier_address'            => 'Supplier Address',
            'receipt_number'              => 'Receipt Number (Belegnummer)',
            'veterinarian'                => 'Veterinarian (if involved)',
            'application_date'            => 'Application Date',
            'colonies_treated_at_stand'   => 'Number of Colonies Treated at Stand',
            'withdrawal_days'             => 'Withdrawal Period (days)',
            'wartezeit_expiry'            => 'Wartezeit Expiry Date',
            'treatment_duration_days'     => 'Treatment Duration (days)',
            'operator_name'               => 'Operator Name',
            'notes'                       => 'Notes',
        ];
    }

    // ── Business logic ────────────────────────────────────────────────────

    /**
     * Automatically calculates wartezeit_expiry before saving.
     * Uses PHP DateTime for correctness across month boundaries (AC-PM-04.3).
     */
    public function beforeSave($insert): bool
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        $appDate         = new \DateTime($this->application_date);
        $withdrawalDays  = (int) $this->withdrawal_days;
        $appDate->modify("+{$withdrawalDays} days");
        $this->wartezeit_expiry = $appDate->format('Y-m-d');

        return true;
    }

    /**
     * After saving, refresh the colony's annual treatment flags (AC-PM-02.6).
     */
    public function afterSave($insert, $changedAttributes): void
    {
        parent::afterSave($insert, $changedAttributes);
        $this->colony->refreshAnnualTreatmentFlags();
    }

    /**
     * Returns true if this treatment's withdrawal period is still active
     * as of the given date.
     */
    public function isInWithdrawal(?string $asOfDate = null): bool
    {
        $asOfDate = $asOfDate ?? date('Y-m-d');
        return $asOfDate < $this->wartezeit_expiry;
    }

    // ── Relations ─────────────────────────────────────────────────────────

    public function getColony(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Colony::class, ['id' => 'colony_id']);
    }

    public function getApiaryStand(): \yii\db\ActiveQuery
    {
        return $this->hasOne(ApiaryStand::class, ['id' => 'apiary_stand_id']);
    }

    // ── Type helpers ──────────────────────────────────────────────────────

    public static function typeLabels(): array
    {
        return [
            self::TYPE_VARROA        => 'Varroa',
            self::TYPE_TRACHEENMILBE => 'Tracheenmilbe',
            self::TYPE_OTHER         => 'Other',
        ];
    }
}

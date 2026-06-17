<?php

declare(strict_types=1);

namespace common\models;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * Colony — individual bee colony assigned to an apiary stand.
 * Carries disease flag state and annual treatment compliance flags (§§ 14–15 BienSeuchV).
 *
 * @property int         $id
 * @property string      $colony_code
 * @property int         $apiary_stand_id
 * @property int         $queen_year
 * @property string      $status
 * @property int|null    $annual_varroa_treated
 * @property int|null    $annual_trachea_treated
 * @property int         $disease_flag
 * @property string|null $disease_flag_note
 * @property int|null    $disease_flag_set_at
 * @property int|null    $disease_flag_set_by
 * @property int|null    $disease_flag_cleared_at
 * @property int|null    $disease_flag_cleared_by
 * @property string|null $disease_flag_resolution
 * @property int         $created_at
 * @property int         $updated_at
 * @property int         $created_by
 */
class Colony extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%colony}}';
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
            [['colony_code', 'apiary_stand_id', 'queen_year'], 'required'],
            [['colony_code'], 'unique'],
            [['colony_code'], 'string', 'max' => 50],
            [['apiary_stand_id', 'queen_year'], 'integer'],
            [['queen_year'], 'integer', 'min' => 2000, 'max' => (int) date('Y')],
            [['status'], 'in', 'range' => ['active', 'inactive', 'lost']],
            [['annual_varroa_treated', 'annual_trachea_treated', 'disease_flag'], 'boolean'],
            [['disease_flag_note', 'disease_flag_resolution'], 'string'],
            [['apiary_stand_id'], 'exist', 'targetClass' => ApiaryStand::class, 'targetAttribute' => 'id'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'colony_code'            => 'Colony Code',
            'apiary_stand_id'        => 'Apiary Stand',
            'queen_year'             => 'Queen Year',
            'status'                 => 'Colony Status',
            'annual_varroa_treated'  => 'Annual Varroa Treatment Completed',
            'annual_trachea_treated' => 'Annual Tracheenmilbe Treatment Completed',
            'disease_flag'           => 'Active Disease Concern',
        ];
    }

    // ── Relations ─────────────────────────────────────────────────────────

    public function getApiaryStand(): \yii\db\ActiveQuery
    {
        return $this->hasOne(ApiaryStand::class, ['id' => 'apiary_stand_id']);
    }

    public function getInspections(): \yii\db\ActiveQuery
    {
        return $this->hasMany(Inspection::class, ['colony_id' => 'id'])
            ->orderBy(['inspection_date' => SORT_DESC]);
    }

    public function getTreatments(): \yii\db\ActiveQuery
    {
        return $this->hasMany(Treatment::class, ['colony_id' => 'id'])
            ->orderBy(['application_date' => SORT_DESC]);
    }

    public function getBatches(): \yii\db\ActiveQuery
    {
        return $this->hasMany(Batch::class, ['id' => 'batch_id'])
            ->viaTable('{{%batch_colony}}', ['colony_id' => 'id'])
            ->orderBy(['harvest_date' => SORT_DESC]);
    }

    public function getMovements(): \yii\db\ActiveQuery
    {
        return $this->hasMany(ColonyMovement::class, ['colony_id' => 'id'])
            ->orderBy(['movement_date' => SORT_DESC]);
    }

    // ── Withdrawal period ─────────────────────────────────────────────────

    /**
     * Returns the latest Wartezeit expiry date across all treatments for this colony.
     * Returns null if no treatments have been recorded.
     */
    public function getLatestWartezeitExpiry(): ?string
    {
        return Treatment::find()
            ->where(['colony_id' => $this->id])
            ->max('wartezeit_expiry');
    }

    /**
     * Returns true if the colony's withdrawal period has cleared as of the given date.
     * A colony with no treatments is always clear.
     */
    public function isWithdrawalCleared(?string $asOfDate = null): bool
    {
        $expiry = $this->getLatestWartezeitExpiry();
        if ($expiry === null) {
            return true;
        }
        $asOfDate = $asOfDate ?? date('Y-m-d');
        return $asOfDate >= $expiry;
    }

    /**
     * Returns true if the colony is eligible to contribute to a batch
     * with the given harvest date — withdrawal cleared and no disease flag.
     */
    public function isEligibleForHarvest(string $harvestDate): bool
    {
        if ($this->disease_flag) {
            return false;
        }
        return $this->isWithdrawalCleared($harvestDate);
    }

    // ── Disease flag management ───────────────────────────────────────────

    /**
     * Sets the disease flag on the colony.
     * Records who set it and when (AC-CO-05.2).
     */
    public function setDiseaseFlag(string $note): bool
    {
        $this->disease_flag          = 1;
        $this->disease_flag_note     = $note;
        $this->disease_flag_set_at   = time();
        $this->disease_flag_set_by   = Yii::$app->user->id;
        $this->disease_flag_cleared_at  = null;
        $this->disease_flag_cleared_by  = null;
        $this->disease_flag_resolution  = null;
        return $this->save(false);
    }

    /**
     * Clears the disease flag on the colony.
     * Records who cleared it and when (AC-CO-05.4).
     */
    public function clearDiseaseFlag(string $resolution = ''): bool
    {
        $this->disease_flag             = 0;
        $this->disease_flag_cleared_at  = time();
        $this->disease_flag_cleared_by  = Yii::$app->user->id;
        $this->disease_flag_resolution  = $resolution;
        return $this->save(false);
    }

    // ── Annual treatment flags ────────────────────────────────────────────

    /**
     * Updates annual treatment compliance flags based on treatments
     * recorded in the current calendar year.
     * Called automatically after a treatment is saved (AC-PM-02.6).
     */
    public function refreshAnnualTreatmentFlags(): void
    {
        $year      = (int) date('Y');
        $yearStart = $year . '-01-01';
        $yearEnd   = $year . '-12-31';

        $varroaTreated = Treatment::find()
            ->where(['colony_id' => $this->id, 'treatment_type' => 'varroa'])
            ->andWhere(['between', 'application_date', $yearStart, $yearEnd])
            ->exists();

        $tracheaTreated = Treatment::find()
            ->where(['colony_id' => $this->id, 'treatment_type' => 'tracheenmilbe'])
            ->andWhere(['between', 'application_date', $yearStart, $yearEnd])
            ->exists();

        $this->annual_varroa_treated  = $varroaTreated ? 1 : 0;
        $this->annual_trachea_treated = $tracheaTreated ? 1 : 0;
        $this->save(false);
    }

    // ── Stockkarte ────────────────────────────────────────────────────────

    /**
     * Returns all Stockkarte entries (inspections, treatments, harvests)
     * for this colony in a single chronological array.
     * Each entry has: type, date, record.
     */
    public function getStockkarte(): array
    {
        $entries = [];

        foreach ($this->inspections as $i) {
            $entries[] = ['type' => 'inspection', 'date' => $i->inspection_date, 'record' => $i];
        }
        foreach ($this->treatments as $t) {
            $entries[] = ['type' => 'treatment', 'date' => $t->application_date, 'record' => $t];
        }
        foreach ($this->batches as $b) {
            $entries[] = ['type' => 'harvest', 'date' => $b->harvest_date, 'record' => $b];
        }

        usort($entries, static function (array $a, array $b): int {
            return strcmp($b['date'], $a['date']); // descending
        });

        return $entries;
    }
}

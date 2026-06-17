<?php

declare(strict_types=1);

namespace common\models;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * Batch — honey production lot.
 * Linked to source colonies via batch_colony pivot.
 * Carries the five-check release gate logic (FR8, US-CO-01).
 *
 * Status lifecycle: pending_release → released | review_required
 *
 * @property int         $id
 * @property string      $lot_number
 * @property string      $harvest_date
 * @property int         $apiary_stand_id
 * @property float       $harvest_quantity_kg
 * @property string      $honey_variety
 * @property float|null  $water_content
 * @property float|null  $hmf
 * @property float|null  $conductivity
 * @property string|null $fill_date
 * @property string|null $container_size
 * @property int|null    $packaged_unit_count
 * @property string|null $best_before_date
 * @property string|null $origin_statement
 * @property int         $haccp_confirmed
 * @property string      $status
 * @property int|null    $released_at
 * @property int|null    $released_by
 * @property int         $created_at
 * @property int         $updated_at
 * @property int         $created_by
 */
class Batch extends ActiveRecord
{
    public const STATUS_PENDING_RELEASE = 'pending_release';
    public const STATUS_RELEASED        = 'released';
    public const STATUS_REVIEW_REQUIRED = 'review_required';

    // HonigV water content limits per honey variety
    public const WATER_LIMIT_STANDARD = 20.0;
    public const WATER_LIMIT_HEIDEHONIG = 23.0;

    // Honey varieties that use the Heidehonig water content limit
    private const HEIDEHONIG_VARIETIES = ['Heidehonig', 'Heidehonig (Calluna)'];

    /**
     * Selectable honey varieties for the harvest and batch-details dropdowns.
     * The column remains free-text; the dropdown only constrains input.
     *
     * @return array<string,string>
     */
    public static function honeyVarietyOptions(): array
    {
        $varieties = ['Blütenhonig', 'Waldhonig', 'Rapshonig', 'Heidehonig', 'Other'];
        return array_combine($varieties, $varieties);
    }

    // HMF statutory maximum (HonigV § 3)
    public const HMF_LIMIT = 40.0;

    // Conductivity guideline thresholds (mS/cm) — informational only
    public const CONDUCTIVITY_THRESHOLD = 0.8;

    /**
     * Container sizes for the batch-details dropdown.
     * The stored value is the display label (the column is varchar);
     * the mapped value is its gram equivalent for yield calculation.
     *
     * @return array<string,int> label => grams
     */
    public static function containerSizeOptions(): array
    {
        return [
            '250g'                            => 250,
            '500g'                            => 500,
            '1kg (1000g)'                     => 1000,
            '2kg (2000g)'                     => 2000,
            '5kg (5000g)'                     => 5000,
            '25kg (25000g — wholesale pail)'  => 25000,
        ];
    }

    /**
     * Resolves a stored container_size label to its gram equivalent.
     * Falls back to parsing a leading "<n>kg" / "<n>g" token so legacy
     * free-text values still yield a number. Returns null if unknown.
     */
    public static function containerSizeGrams(?string $label): ?int
    {
        if ($label === null || $label === '') {
            return null;
        }
        $map = self::containerSizeOptions();
        if (isset($map[$label])) {
            return $map[$label];
        }
        if (preg_match('/([\d.]+)\s*(kg|g)/i', $label, $m)) {
            $value = (float) $m[1];
            return (int) (strtolower($m[2]) === 'kg' ? $value * 1000 : $value);
        }
        return null;
    }

    public static function tableName(): string
    {
        return '{{%batch}}';
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
            [['lot_number', 'harvest_date', 'apiary_stand_id', 'harvest_quantity_kg', 'honey_variety'], 'required'],
            [['lot_number'], 'unique'],
            [['lot_number'], 'string', 'max' => 50],
            [['harvest_date', 'fill_date', 'best_before_date'], 'date', 'format' => 'php:Y-m-d'],
            [['apiary_stand_id', 'packaged_unit_count'], 'integer'],
            [['harvest_quantity_kg'], 'number', 'min' => 0.01],
            [['water_content'], 'number', 'min' => 0, 'max' => 100],
            // HMF must not exceed the HonigV § 3 statutory maximum of 40 mg/kg.
            [['hmf'], 'number', 'min' => 0, 'max' => self::HMF_LIMIT,
                'tooBig' => 'HMF must not exceed 40 mg/kg (HonigV § 3).'],
            [['conductivity'], 'number', 'min' => 0],
            [['haccp_confirmed'], 'boolean'],
            [['honey_variety', 'container_size'], 'string', 'max' => 100],
            [['origin_statement'], 'string', 'max' => 255],
            [['status'], 'in', 'range' => [
                self::STATUS_PENDING_RELEASE,
                self::STATUS_RELEASED,
                self::STATUS_REVIEW_REQUIRED,
            ]],
            [['apiary_stand_id'], 'exist', 'targetClass' => ApiaryStand::class, 'targetAttribute' => 'id'],

            // Water content validated against HonigV limit for the declared variety
            [['water_content'], 'validateWaterContent'],

            // Date ordering: harvest → fill → best before
            [['fill_date'], 'validateDateOrder'],
        ];
    }

    /**
     * Validates the chronological order of the batch dates:
     *   fill date must be on or after the harvest date;
     *   best before date must be after the fill date.
     * Dates are stored as Y-m-d so string comparison is chronological.
     */
    public function validateDateOrder(string $attribute): void
    {
        if (!empty($this->fill_date) && !empty($this->harvest_date)
            && $this->fill_date < $this->harvest_date) {
            $this->addError('fill_date', 'Fill date must be on or after the harvest date.');
        }
        if (!empty($this->best_before_date) && !empty($this->fill_date)
            && $this->best_before_date <= $this->fill_date) {
            $this->addError('best_before_date', 'Best before date must be after the fill date.');
        }
    }

    /**
     * Returns an informational conductivity warning, or null.
     * Waldhonig should be ≥ 0.8 mS/cm; Blütenhonig should be ≤ 0.8 mS/cm.
     * This never blocks saving — conductivity is informational.
     */
    public function conductivityWarning(): ?string
    {
        if ($this->conductivity === null) {
            return null;
        }
        $value = (float) $this->conductivity;
        if ($this->honey_variety === 'Waldhonig' && $value < self::CONDUCTIVITY_THRESHOLD) {
            return sprintf(
                'Conductivity %.3f mS/cm is below 0.8 mS/cm, which is unusual for Waldhonig.',
                $value,
            );
        }
        if ($this->honey_variety === 'Blütenhonig' && $value > self::CONDUCTIVITY_THRESHOLD) {
            return sprintf(
                'Conductivity %.3f mS/cm is above 0.8 mS/cm, which is unusual for Blütenhonig.',
                $value,
            );
        }
        return null;
    }

    /**
     * Validates water content against the HonigV statutory limit
     * for the declared honey variety (AC-PM-06.3).
     */
    public function validateWaterContent(string $attribute): void
    {
        if ($this->water_content === null) {
            return;
        }
        $limit = $this->getWaterContentLimit();
        if ((float) $this->water_content > $limit) {
            $this->addError($attribute, sprintf(
                'Water content %.1f%% exceeds the HonigV limit of %.0f%% for %s.',
                $this->water_content,
                $limit,
                $this->honey_variety
            ));
        }
    }

    public function attributeLabels(): array
    {
        return [
            'lot_number'          => 'Lot Number (Losnummer)',
            'harvest_date'        => 'Harvest Date',
            'apiary_stand_id'     => 'Apiary Stand',
            'harvest_quantity_kg' => 'Harvest Quantity (kg)',
            'honey_variety'       => 'Honey Variety (Sortenbezeichnung)',
            'water_content'       => 'Water Content (%)',
            'hmf'                 => 'HMF (mg/kg)',
            'conductivity'        => 'Conductivity (mS/cm)',
            'fill_date'           => 'Fill Date',
            'container_size'      => 'Container Size',
            'packaged_unit_count' => 'Packaged Unit Count',
            'best_before_date'    => 'Best Before Date',
            'origin_statement'    => 'Origin Statement',
            'haccp_confirmed'     => 'HACCP Process Confirmed',
            'status'              => 'Status',
        ];
    }

    // ── Relations ─────────────────────────────────────────────────────────

    public function getApiaryStand(): \yii\db\ActiveQuery
    {
        return $this->hasOne(ApiaryStand::class, ['id' => 'apiary_stand_id']);
    }

    public function getColonies(): \yii\db\ActiveQuery
    {
        return $this->hasMany(Colony::class, ['id' => 'colony_id'])
            ->viaTable('{{%batch_colony}}', ['batch_id' => 'id']);
    }

    public function getProducts(): \yii\db\ActiveQuery
    {
        return $this->hasMany(Product::class, ['batch_id' => 'id']);
    }

    // ── Water content limit ───────────────────────────────────────────────

    public function getWaterContentLimit(): float
    {
        return in_array($this->honey_variety, self::HEIDEHONIG_VARIETIES, true)
            ? self::WATER_LIMIT_HEIDEHONIG
            : self::WATER_LIMIT_STANDARD;
    }

    // ── Five-check release gate ───────────────────────────────────────────

    /**
     * Evaluates all five release gate conditions.
     * Returns an array keyed by check label, each with:
     *   - passed (bool)
     *   - reason (string)
     *
     * (FR8, US-CO-01, AC-CO-01.1)
     */
    public function getReleaseGateChecks(): array
    {
        $checks = [];

        // 1. Treatment withdrawal cleared for all source colonies
        $blockedColonies = [];
        foreach ($this->colonies as $colony) {
            if (!$colony->isWithdrawalCleared($this->harvest_date)) {
                $blockedColonies[] = ['id' => $colony->id, 'code' => $colony->colony_code];
            }
        }
        $withdrawalPassed = empty($blockedColonies);
        $checks['Treatment withdrawal cleared'] = [
            'type'     => 'withdrawal',
            'passed'   => $withdrawalPassed,
            'reason'   => $withdrawalPassed
                ? 'All source colonies clear'
                : 'Blocked by: ' . implode(', ', array_column($blockedColonies, 'code')),
            'colonies' => $blockedColonies,
        ];

        // 2. Water content within HonigV statutory limit
        $limit       = $this->getWaterContentLimit();
        $waterPassed = $this->water_content !== null && (float) $this->water_content <= $limit;
        $checks[sprintf('Water content ≤ %.0f%% (HonigV)', $limit)] = [
            'type'   => 'water',
            'passed' => $waterPassed,
            'reason' => $this->water_content === null
                ? 'Water content not recorded'
                : ($waterPassed
                    ? sprintf('%.1f%%', $this->water_content)
                    : sprintf('%.1f%% exceeds limit of %.0f%%', $this->water_content, $limit)),
        ];

        // 3. Mandatory HonigV label fields present
        $labelPassed = !empty($this->honey_variety)
            && !empty($this->origin_statement)
            && !empty($this->best_before_date);
        $checks['Label fields complete (HonigV)'] = [
            'type'   => 'label',
            'passed' => $labelPassed,
            'reason' => $labelPassed
                ? 'All mandatory label fields present'
                : 'Missing: ' . implode(', ', array_filter([
                    empty($this->honey_variety)    ? 'Sortenbezeichnung' : null,
                    empty($this->origin_statement) ? 'Origin statement'  : null,
                    empty($this->best_before_date) ? 'Best before date'  : null,
                ])),
        ];

        // 4. No active disease flag on any source colony
        $flaggedColonies = [];
        foreach ($this->colonies as $colony) {
            if ($colony->disease_flag) {
                $flaggedColonies[] = ['id' => $colony->id, 'code' => $colony->colony_code];
            }
        }
        $diseasePassed = empty($flaggedColonies);
        $checks['No active disease flag'] = [
            'type'     => 'disease',
            'passed'   => $diseasePassed,
            'reason'   => $diseasePassed
                ? 'No disease concerns'
                : 'Active flag on: ' . implode(', ', array_column($flaggedColonies, 'code')),
            'colonies' => $flaggedColonies,
        ];

        // 5. HACCP process confirmation recorded
        $checks['HACCP confirmed'] = [
            'type'   => 'haccp',
            'passed' => (bool) $this->haccp_confirmed,
            'reason' => $this->haccp_confirmed
                ? 'HACCP process confirmed'
                : 'HACCP process confirmation not recorded',
        ];

        return $checks;
    }

    /**
     * Returns true if all five release gate conditions pass.
     */
    public function canBeReleased(): bool
    {
        foreach ($this->getReleaseGateChecks() as $check) {
            if (!$check['passed']) {
                return false;
            }
        }
        return true;
    }

    /**
     * Releases the batch.
     * Records releasing user and timestamp (AC-CO-02.2).
     * Locks all production fields (AC-CO-02.4).
     */
    public function release(): bool
    {
        if (!$this->canBeReleased()) {
            return false;
        }
        $this->status      = self::STATUS_RELEASED;
        $this->released_at = time();
        $this->released_by = Yii::$app->user->id;
        return $this->save(false);
    }

    public function isReleased(): bool
    {
        return $this->status === self::STATUS_RELEASED;
    }

    public function isPendingRelease(): bool
    {
        return $this->status === self::STATUS_PENDING_RELEASE;
    }

    /**
     * Generates the next lot number in sequence: LIN-YYYY-NNN
     */
    public static function generateLotNumber(): string
    {
        $year  = date('Y');
        $count = (int) self::find()->where(['like', 'lot_number', "LIN-{$year}-"])->count();
        return sprintf('LIN-%s-%03d', $year, $count + 1);
    }
}

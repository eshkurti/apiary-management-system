<?php

declare(strict_types=1);

namespace common\models;

use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * Apiary Stand — physical registered location.
 * Each stand has its own Veterinäramt authority registration number (§ 1a BienSeuchV).
 *
 * @property int    $id
 * @property string $stand_code
 * @property string $name
 * @property float  $latitude
 * @property float  $longitude
 * @property string $landkreis
 * @property string $authority_reg_number
 * @property int    $is_active
 * @property int    $created_at
 * @property int    $updated_at
 * @property int    $created_by
 */
class ApiaryStand extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%apiary_stand}}';
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
            [['stand_code', 'name', 'latitude', 'longitude', 'landkreis', 'authority_reg_number'], 'required'],
            [['stand_code'], 'unique'],
            [['stand_code'], 'string', 'max' => 50],
            [['name'], 'string', 'max' => 150],
            [['latitude', 'longitude'], 'number'],
            [['latitude'], 'number', 'min' => -90, 'max' => 90],
            [['longitude'], 'number', 'min' => -180, 'max' => 180],
            [['landkreis', 'authority_reg_number'], 'string', 'max' => 100],
            [['is_active'], 'boolean'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'stand_code'           => 'Stand Code',
            'name'                 => 'Stand Name',
            'latitude'             => 'Latitude',
            'longitude'            => 'Longitude',
            'landkreis'            => 'Landkreis',
            'authority_reg_number' => 'Authority Registration Number',
            'is_active'            => 'Active',
        ];
    }

    public function getColonies(): \yii\db\ActiveQuery
    {
        return $this->hasMany(Colony::class, ['apiary_stand_id' => 'id'])
            ->orderBy(['colony_code' => SORT_ASC]);
    }

    public function getActiveColonies(): \yii\db\ActiveQuery
    {
        return $this->hasMany(Colony::class, ['apiary_stand_id' => 'id'])
            ->where(['status' => 'active'])
            ->orderBy(['colony_code' => SORT_ASC]);
    }

    public function getTreatments(): \yii\db\ActiveQuery
    {
        return $this->hasMany(Treatment::class, ['apiary_stand_id' => 'id'])
            ->orderBy(['application_date' => SORT_DESC]);
    }

    public function getBatches(): \yii\db\ActiveQuery
    {
        return $this->hasMany(Batch::class, ['apiary_stand_id' => 'id'])
            ->orderBy(['harvest_date' => SORT_DESC]);
    }

    /**
     * Returns the count of active colonies currently assigned to this stand.
     */
    public function getActiveColonyCount(): int
    {
        return (int) Colony::find()
            ->where(['apiary_stand_id' => $this->id, 'status' => 'active'])
            ->count();
    }
}

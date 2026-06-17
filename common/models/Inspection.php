<?php

declare(strict_types=1);

namespace common\models;

use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * Inspection — colony health observation forming part of the digital Stockkarte.
 * Inspector identity is captured via created_by (BlameableBehavior).
 * The apiary_stand_id is stored at inspection time so records remain
 * accurate when the colony later moves to a different stand.
 *
 * @property int         $id
 * @property int         $colony_id
 * @property int         $apiary_stand_id
 * @property string      $inspection_date
 * @property string|null $weather
 * @property int|null    $brood_pattern_score
 * @property int         $queen_sighted
 * @property string|null $disease_indicators
 * @property string|null $notes
 * @property int         $feeding_applied
 * @property string|null $feeding_quantity
 * @property int         $created_at
 * @property int         $updated_at
 * @property int         $created_by
 */
class Inspection extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%inspection}}';
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
            [['colony_id', 'apiary_stand_id', 'inspection_date'], 'required'],
            [['colony_id', 'apiary_stand_id', 'brood_pattern_score'], 'integer'],
            [['brood_pattern_score'], 'integer', 'min' => 1, 'max' => 5],
            [['queen_sighted', 'feeding_applied'], 'boolean'],
            [['feeding_quantity'], 'string', 'max' => 50],
            [['inspection_date'], 'date', 'format' => 'php:Y-m-d'],
            [['inspection_date'], 'compare',
                'compareValue' => date('Y-m-d'),
                'operator'     => '<=',
                'message'      => 'Inspection date cannot be in the future (AC-PM-03.3).'],
            [['weather', 'disease_indicators'], 'string', 'max' => 255],
            [['notes'], 'string'],
            [['colony_id'], 'exist', 'targetClass' => Colony::class, 'targetAttribute' => 'id'],
            [['apiary_stand_id'], 'exist', 'targetClass' => ApiaryStand::class, 'targetAttribute' => 'id'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'colony_id'           => 'Colony',
            'apiary_stand_id'     => 'Apiary Stand',
            'inspection_date'     => 'Inspection Date',
            'weather'             => 'Weather Conditions',
            'brood_pattern_score' => 'Brood Pattern Score (1–5)',
            'queen_sighted'       => 'Queen Sighted',
            'disease_indicators'  => 'Disease Indicators',
            'notes'               => 'Observations',
            'feeding_applied'     => 'Feeding applied',
            'feeding_quantity'    => 'Feeding quantity',
        ];
    }

    public function getColony(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Colony::class, ['id' => 'colony_id']);
    }

    public function getApiaryStand(): \yii\db\ActiveQuery
    {
        return $this->hasOne(ApiaryStand::class, ['id' => 'apiary_stand_id']);
    }

    public function getInspector(): \yii\db\ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }
}

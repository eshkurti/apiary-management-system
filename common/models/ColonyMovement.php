<?php

declare(strict_types=1);

namespace common\models;

use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * Colony Movement — records when a colony moves between apiary stands.
 * Historical inspection and treatment records retain their original stand_id.
 *
 * @property int         $id
 * @property int         $colony_id
 * @property int         $from_stand_id
 * @property int         $to_stand_id
 * @property string      $movement_date
 * @property string|null $notes
 * @property int         $created_at
 * @property int         $created_by
 */
class ColonyMovement extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%colony_movement}}';
    }

    public function behaviors(): array
    {
        return [
            [
                'class'              => TimestampBehavior::class,
                'updatedAtAttribute' => false,
            ],
            [
                'class'              => BlameableBehavior::class,
                'updatedByAttribute' => false,
            ],
        ];
    }

    public function rules(): array
    {
        return [
            [['colony_id', 'from_stand_id', 'to_stand_id', 'movement_date'], 'required'],
            [['colony_id', 'from_stand_id', 'to_stand_id'], 'integer'],
            [['movement_date'], 'date', 'format' => 'php:Y-m-d'],
            [['movement_date'], 'compare', 'compareValue' => date('Y-m-d'),
                'operator' => '<=', 'message' => 'Movement date cannot be in the future.'],
            [['from_stand_id', 'to_stand_id'], 'compare',
                'compareAttribute' => 'from_stand_id', 'operator' => '!=',
                'when' => fn($model) => $model->to_stand_id !== null,
                'message' => 'Origin and destination stand must be different.'],
            [['notes'], 'string'],
            [['colony_id'], 'exist', 'targetClass' => Colony::class, 'targetAttribute' => 'id'],
            [['from_stand_id'], 'exist', 'targetClass' => ApiaryStand::class, 'targetAttribute' => 'id'],
            [['to_stand_id'], 'exist', 'targetClass' => ApiaryStand::class, 'targetAttribute' => 'id'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'colony_id'     => 'Colony',
            'from_stand_id' => 'From Stand',
            'to_stand_id'   => 'To Stand',
            'movement_date' => 'Movement Date',
            'notes'         => 'Notes',
        ];
    }

    public function getColony(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Colony::class, ['id' => 'colony_id']);
    }

    public function getFromStand(): \yii\db\ActiveQuery
    {
        return $this->hasOne(ApiaryStand::class, ['id' => 'from_stand_id']);
    }

    public function getToStand(): \yii\db\ActiveQuery
    {
        return $this->hasOne(ApiaryStand::class, ['id' => 'to_stand_id']);
    }

    /**
     * After saving a movement, update the colony's current apiary_stand_id.
     */
    public function afterSave($insert, $changedAttributes): void
    {
        parent::afterSave($insert, $changedAttributes);

        if ($insert) {
            Colony::updateAll(
                ['apiary_stand_id' => $this->to_stand_id],
                ['id' => $this->colony_id]
            );
        }
    }
}

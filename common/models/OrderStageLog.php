<?php

declare(strict_types=1);

namespace common\models;

use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * Order Stage Log — audit trail for fulfilment stage transitions.
 *
 * @property int         $id
 * @property int         $order_id
 * @property string|null $from_status
 * @property string      $to_status
 * @property string|null $notes
 * @property int         $created_at
 * @property int         $created_by
 */
class OrderStageLog extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%order_stage_log}}';
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
            [['order_id', 'to_status'], 'required'],
            [['order_id'], 'integer'],
            [['from_status', 'to_status'], 'string', 'max' => 20],
            [['notes'], 'string'],
        ];
    }

    public function getOrder(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Order::class, ['id' => 'order_id']);
    }
}

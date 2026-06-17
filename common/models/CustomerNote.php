<?php

declare(strict_types=1);

namespace common\models;

use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * Customer Note — CRM communication note on a customer record (AC-EC-07.3).
 *
 * @property int    $id
 * @property int    $customer_id
 * @property string $note
 * @property int    $created_at
 * @property int    $created_by
 */
class CustomerNote extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%customer_note}}';
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
            [['customer_id', 'note'], 'required'],
            [['customer_id'], 'integer'],
            [['note'], 'string'],
            [['customer_id'], 'exist', 'targetClass' => Customer::class, 'targetAttribute' => 'id'],
        ];
    }

    public function getCustomer(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Customer::class, ['id' => 'customer_id']);
    }
}

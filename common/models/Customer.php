<?php

declare(strict_types=1);

namespace common\models;

use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * Customer — CRM record for retail and wholesale buyers.
 *
 * @property int         $id
 * @property int|null    $user_id
 * @property string      $name
 * @property string      $email
 * @property string|null $phone
 * @property string|null $company
 * @property string|null $address
 * @property string|null $postcode
 * @property string|null $city
 * @property string      $country
 * @property int         $is_wholesale
 * @property int|null    $min_order_quantity
 * @property int         $is_active
 * @property int         $created_at
 * @property int         $updated_at
 */
class Customer extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%customer}}';
    }

    public function behaviors(): array
    {
        return [TimestampBehavior::class];
    }

    public function rules(): array
    {
        return [
            [['name', 'email'], 'required'],
            [['email'], 'email'],
            [['email'], 'unique'],
            [['name', 'company'], 'string', 'max' => 150],
            [['email'], 'string', 'max' => 150],
            [['phone', 'postcode', 'city', 'country'], 'string', 'max' => 100],
            [['address'], 'string', 'max' => 255],
            [['is_wholesale', 'is_active'], 'boolean'],
            [['min_order_quantity'], 'integer', 'min' => 1],
            [['min_order_quantity'], 'required', 'when' => fn($m) => $m->is_wholesale,
                'message' => 'Minimum order quantity is required for wholesale accounts.'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'name'               => 'Full Name',
            'email'              => 'Email Address',
            'phone'              => 'Phone',
            'company'            => 'Company',
            'address'            => 'Street Address',
            'postcode'           => 'Postcode',
            'city'               => 'City',
            'country'            => 'Country',
            'is_wholesale'       => 'Wholesale Account',
            'min_order_quantity' => 'Minimum Order Quantity',
            'is_active'          => 'Active',
        ];
    }

    public function getOrders(): \yii\db\ActiveQuery
    {
        return $this->hasMany(Order::class, ['customer_id' => 'id'])
            ->orderBy(['order_date' => SORT_DESC]);
    }

    public function getNotes(): \yii\db\ActiveQuery
    {
        return $this->hasMany(CustomerNote::class, ['customer_id' => 'id'])
            ->orderBy(['created_at' => SORT_DESC]);
    }

    public function getUser(): \yii\db\ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function getOrderCount(): int
    {
        return (int) Order::find()->where(['customer_id' => $this->id])->count();
    }
}

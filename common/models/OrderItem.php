<?php

declare(strict_types=1);

namespace common\models;

use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * Order Item — line item with permanent lot_number and product_name snapshots.
 * The lot_number snapshot enables recall traceability even if the product
 * or batch record is later modified (FR15, AC-EC-03.4).
 *
 * @property int    $id
 * @property int    $order_id
 * @property int    $product_id
 * @property int    $batch_id
 * @property string $lot_number
 * @property string $product_name
 * @property int    $quantity
 * @property float  $unit_price
 * @property float  $line_total
 */
class OrderItem extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%order_item}}';
    }

    public function rules(): array
    {
        return [
            [['order_id', 'product_id', 'batch_id', 'lot_number', 'product_name', 'quantity', 'unit_price', 'line_total'], 'required'],
            [['order_id', 'product_id', 'batch_id', 'quantity'], 'integer'],
            [['quantity'], 'integer', 'min' => 1],
            [['unit_price', 'line_total'], 'number', 'min' => 0],
            [['lot_number', 'product_name'], 'string', 'max' => 150],
        ];
    }

    public function getOrder(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Order::class, ['id' => 'order_id']);
    }

    public function getProduct(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Product::class, ['id' => 'product_id']);
    }

    public function getBatch(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Batch::class, ['id' => 'batch_id']);
    }
}

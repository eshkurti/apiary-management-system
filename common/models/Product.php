<?php

declare(strict_types=1);

namespace common\models;

use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * Product — shop product derived from a released batch.
 * Provenance fields (lot number, honey variety, etc.) are inherited from
 * the linked batch and exposed to customers in the shop (AC-EC-05.3).
 *
 * @property int         $id
 * @property int         $batch_id
 * @property string      $name
 * @property string|null $description
 * @property float       $price
 * @property float|null  $wholesale_price
 * @property int         $stock_quantity
 * @property int         $is_published
 * @property int         $review_unpublished
 * @property int         $created_at
 * @property int         $updated_at
 * @property int         $created_by
 */
class Product extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%product}}';
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
            [['batch_id', 'name', 'price'], 'required'],
            [['batch_id', 'stock_quantity'], 'integer'],
            [['stock_quantity'], 'integer', 'min' => 0],
            // A brand-new product must list at least one unit — a zero-stock
            // product has no purpose. Existing products may legitimately reach
            // zero (sold through) and must still be saveable, so this only
            // applies on creation.
            [['stock_quantity'], 'validateStockNotZeroOnCreate'],
            [['price'], 'number', 'min' => 0.01],
            [['wholesale_price'], 'number', 'min' => 0.01],
            [['wholesale_price'], 'default', 'value' => null],
            [['is_published', 'review_unpublished'], 'boolean'],
            [['name'], 'string', 'max' => 150],
            [['description'], 'string'],
            [['batch_id'], 'exist', 'targetClass' => Batch::class, 'targetAttribute' => 'id'],
            // AC-EC-05.1: only released batches can be published
            [['batch_id'], 'validateBatchReleased', 'when' => fn($m) => $m->is_published],

            // Wholesale price (if set) must be below the retail price.
            [['wholesale_price'], 'validateWholesaleBelowRetail'],

            // Stock cannot exceed the batch's theoretical maximum yield.
            [['stock_quantity'], 'validateStockWithinYield'],
        ];
    }

    /**
     * A new product must list at least one unit (AC: zero-stock products serve
     * no purpose). Only enforced on creation so sold-through products — which
     * naturally reach zero — can still be edited, unpublished, and saved.
     */
    public function validateStockNotZeroOnCreate(string $attribute): void
    {
        if ($this->isNewRecord && (int) $this->stock_quantity < 1) {
            $this->addError($attribute, 'A new product must list at least 1 unit.');
        }
    }

    /**
     * A set wholesale price must be strictly lower than the retail price.
     */
    public function validateWholesaleBelowRetail(string $attribute): void
    {
        if ($this->wholesale_price === null || $this->wholesale_price === '') {
            return;
        }
        if ((float) $this->wholesale_price >= (float) $this->price) {
            $this->addError($attribute, 'Wholesale price must be lower than the standard retail price.');
        }
    }

    /**
     * Stock quantity must not exceed the units still available from the source
     * batch — its available units minus the stock already allocated to the
     * batch's other products (including unpublished ones). This prevents
     * over-allocating a batch across multiple sibling products.
     */
    public function validateStockWithinYield(string $attribute): void
    {
        $batch = $this->batch;
        if ($batch === null || $batch->availableUnits() === null) {
            return; // no determinable cap (no packaged count and no container size)
        }

        // remainingUnits() excludes this product's own existing allocation,
        // so on edit we compare against what the *other* products have taken.
        $remaining = $batch->remainingUnits($this->id);
        if ((int) $this->stock_quantity > $remaining) {
            $available = max(0, $remaining);
            $this->addError($attribute, sprintf(
                'Only %d unit%s remain available from batch %s — the rest is already '
                . 'allocated to other products. Reduce the quantity to fit.',
                $available,
                $available === 1 ? '' : 's',
                $batch->lot_number,
            ));
        }
    }

    /**
     * Theoretical maximum packaged units for the linked batch:
     *   (harvest_quantity_kg × 1000) ÷ container_size_grams.
     * Returns null when the batch, harvest quantity, or container size is unknown.
     */
    public function theoreticalMaxUnits(): ?int
    {
        $batch = $this->batch;
        if ($batch === null || empty($batch->harvest_quantity_kg)) {
            return null;
        }
        $grams = Batch::containerSizeGrams($batch->container_size);
        if ($grams === null || $grams <= 0) {
            return null;
        }
        return (int) floor(((float) $batch->harvest_quantity_kg * 1000) / $grams);
    }

    public function validateBatchReleased(string $attribute): void
    {
        $batch = Batch::findOne($this->batch_id);
        if (!$batch || !$batch->isReleased()) {
            $this->addError($attribute, 'A product can only be published from a released batch (AC-EC-05.1).');
        }
    }

    /**
     * Silently revert is_published to 0 if batch is not released (AC-EC-05.1).
     */
    public function beforeSave($insert): bool
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }
        if ($this->is_published) {
            $batch = $this->batch;
            if (!$batch || !$batch->isReleased()) {
                $this->is_published = 0;
            }
        }
        return true;
    }

    public function attributeLabels(): array
    {
        return [
            'batch_id'       => 'Source Batch',
            'name'           => 'Product Name',
            'description'    => 'Description',
            'price'          => 'Price (€)',
            'wholesale_price'=> 'Wholesale Price (€)',
            'stock_quantity' => 'Stock Quantity',
            'is_published'   => 'Published in Shop',
        ];
    }

    /**
     * Returns the price that applies to the given customer.
     * Wholesale customers pay wholesale_price when it is set; everyone else
     * (and wholesale customers without a wholesale price) pays the standard price.
     */
    public function effectivePrice(?Customer $customer = null): float
    {
        if ($customer !== null && $customer->is_wholesale && $this->wholesale_price !== null) {
            return (float) $this->wholesale_price;
        }
        return (float) $this->price;
    }

    /**
     * Returns true if the given customer is being charged the wholesale price.
     */
    public function isWholesalePriceFor(?Customer $customer): bool
    {
        return $customer !== null
            && $customer->is_wholesale
            && $this->wholesale_price !== null;
    }

    public function getBatch(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Batch::class, ['id' => 'batch_id']);
    }

    public function getOrderItems(): \yii\db\ActiveQuery
    {
        return $this->hasMany(OrderItem::class, ['product_id' => 'id']);
    }
}

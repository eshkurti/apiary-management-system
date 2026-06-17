<?php

declare(strict_types=1);

namespace common\models;

use yii\db\ActiveRecord;

/**
 * TreatmentProduct — reference table of approved German veterinary products
 * used against Varroa and Tracheenmilbe.
 *
 * Read-only reference data: populates the product dropdown on the treatment
 * create form and supplies the typical statutory values (withdrawal period,
 * duration, quantity, supplier) that autofill the treatment record.
 *
 * @property int         $id
 * @property string      $treatment_type
 * @property string      $product_name
 * @property int         $typical_withdrawal_days
 * @property int         $typical_duration_days
 * @property string|null $typical_quantity
 * @property string|null $supplier_name
 * @property string|null $supplier_address
 */
class TreatmentProduct extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%treatment_product}}';
    }

    /**
     * Returns id => product_name map for the products of a given treatment type,
     * suitable for a dropDownList.
     */
    public static function mapForType(string $treatmentType): array
    {
        $rows = static::find()
            ->where(['treatment_type' => $treatmentType])
            ->orderBy(['product_name' => SORT_ASC])
            ->all();

        $map = [];
        foreach ($rows as $row) {
            $map[$row->id] = $row->product_name;
        }
        return $map;
    }

    /**
     * Returns the autofill payload for the treatment form (JSON-serialisable).
     */
    public function toAutofillData(): array
    {
        return [
            'product_name'     => $this->product_name,
            'withdrawal_days'  => $this->typical_withdrawal_days,
            'duration_days'    => $this->typical_duration_days,
            'quantity'         => $this->typical_quantity,
            'supplier_name'    => $this->supplier_name,
            'supplier_address' => $this->supplier_address,
        ];
    }
}

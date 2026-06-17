<?php

declare(strict_types=1);

namespace common\models;

use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * Order — customer purchase.
 * Status advances through four stages: received → packed → shipped → delivered.
 *
 * @property int         $id
 * @property int         $customer_id
 * @property string      $order_number
 * @property string      $order_date
 * @property float       $total_amount
 * @property string      $status
 * @property string|null $shipping_address
 * @property string|null $notes
 * @property int         $created_at
 * @property int         $updated_at
 */
class Order extends ActiveRecord
{
    public const STATUS_RECEIVED  = 'received';
    public const STATUS_PACKED    = 'packed';
    public const STATUS_SHIPPED   = 'shipped';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_CANCELLED = 'cancelled';

    private const STAGE_SEQUENCE = [
        self::STATUS_RECEIVED,
        self::STATUS_PACKED,
        self::STATUS_SHIPPED,
        self::STATUS_DELIVERED,
    ];

    public static function tableName(): string
    {
        return '{{%order}}';
    }

    public function behaviors(): array
    {
        return [TimestampBehavior::class];
    }

    public function rules(): array
    {
        return [
            [['customer_id', 'order_number', 'order_date', 'total_amount'], 'required'],
            [['customer_id'], 'integer'],
            [['total_amount'], 'number', 'min' => 0],
            [['order_number'], 'unique'],
            [['order_number'], 'string', 'max' => 50],
            [['order_date'], 'date', 'format' => 'php:Y-m-d'],
            [['status'], 'in', 'range' => [
                self::STATUS_RECEIVED, self::STATUS_PACKED,
                self::STATUS_SHIPPED, self::STATUS_DELIVERED, self::STATUS_CANCELLED,
            ]],
            [['shipping_address'], 'string', 'max' => 500],
            [['notes'], 'string'],
            [['customer_id'], 'exist', 'targetClass' => Customer::class, 'targetAttribute' => 'id'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'order_number'     => 'Order Number',
            'order_date'       => 'Order Date',
            'total_amount'     => 'Total (€)',
            'status'           => 'Fulfilment Status',
            'shipping_address' => 'Shipping Address',
        ];
    }

    public function getCustomer(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Customer::class, ['id' => 'customer_id']);
    }

    public function getItems(): \yii\db\ActiveQuery
    {
        return $this->hasMany(OrderItem::class, ['order_id' => 'id']);
    }

    public function getStageLog(): \yii\db\ActiveQuery
    {
        return $this->hasMany(OrderStageLog::class, ['order_id' => 'id'])
            ->orderBy(['created_at' => SORT_ASC]);
    }

    /**
     * Returns the next valid status in the sequence, or null if at the end.
     */
    public function getNextStatus(): ?string
    {
        $index = array_search($this->status, self::STAGE_SEQUENCE, true);
        if ($index === false || $index >= count(self::STAGE_SEQUENCE) - 1) {
            return null;
        }
        return self::STAGE_SEQUENCE[$index + 1];
    }

    /**
     * Advances the order to the next fulfilment stage.
     * Records the transition in the stage log (AC-EC-06.2).
     * Cannot skip a stage or move backwards (AC-EC-06.5).
     */
    public function advanceStatus(int $userId, string $notes = ''): bool
    {
        $next = $this->getNextStatus();
        if ($next === null) {
            return false;
        }

        $from         = $this->status;
        $this->status = $next;
        if (!$this->save(false)) {
            return false;
        }

        $log = new OrderStageLog([
            'order_id'    => $this->id,
            'from_status' => $from,
            'to_status'   => $next,
            'notes'       => $notes ?: null,
            'created_by'  => $userId,
        ]);
        return $log->save();
    }

    /**
     * Generates the next order number: ORD-YYYY-NNNN
     */
    public static function generateOrderNumber(): string
    {
        $year  = date('Y');
        $count = (int) self::find()->where(['like', 'order_number', "ORD-{$year}-"])->count();
        return sprintf('ORD-%s-%04d', $year, $count + 1);
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_RECEIVED  => 'Received',
            self::STATUS_PACKED    => 'Packed',
            self::STATUS_SHIPPED   => 'Shipped',
            self::STATUS_DELIVERED => 'Delivered',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }
}

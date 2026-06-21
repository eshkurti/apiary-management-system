<?php

declare(strict_types=1);

namespace common\fixtures;

use common\models\OrderItem;
use yii\test\ActiveFixture;

/**
 * Order-item fixture, the companion to OrderFixture. Loaded with an empty data
 * file so the order_item table starts each test clean.
 */
class OrderItemFixture extends ActiveFixture
{
    public $modelClass = OrderItem::class;
}

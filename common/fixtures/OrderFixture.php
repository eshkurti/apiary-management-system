<?php

declare(strict_types=1);

namespace common\fixtures;

use common\models\Order;
use yii\test\ActiveFixture;

/**
 * Order fixture. Normally loaded with an empty data file so the order table is
 * truncated to a known-clean state before each test — orders created during a
 * test (and any demo-seed rows) must not leak into the next one.
 */
class OrderFixture extends ActiveFixture
{
    public $modelClass = Order::class;
}

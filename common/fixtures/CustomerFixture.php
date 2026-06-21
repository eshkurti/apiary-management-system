<?php

declare(strict_types=1);

namespace common\fixtures;

use common\models\Customer;
use yii\test\ActiveFixture;

/**
 * Customer (CRM) fixture. Data file is supplied per-suite via the Cest's
 * _fixtures().
 */
class CustomerFixture extends ActiveFixture
{
    public $modelClass = Customer::class;
}

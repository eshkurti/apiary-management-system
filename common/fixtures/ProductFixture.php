<?php

declare(strict_types=1);

namespace common\fixtures;

use common\models\Product;
use yii\test\ActiveFixture;

/**
 * Shop product fixture. Data file is supplied per-suite via the Cest's
 * _fixtures(). Rows are inserted verbatim so a published product can be linked
 * to a released batch without going through the publish-time guards.
 */
class ProductFixture extends ActiveFixture
{
    public $modelClass = Product::class;
}

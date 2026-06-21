<?php

declare(strict_types=1);

namespace common\fixtures;

use common\models\Batch;
use yii\test\ActiveFixture;

/**
 * Batch fixture. Data file is supplied per-suite via the Cest's _fixtures().
 */
class BatchFixture extends ActiveFixture
{
    public $modelClass = Batch::class;
}

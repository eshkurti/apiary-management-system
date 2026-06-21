<?php

declare(strict_types=1);

namespace common\fixtures;

use common\models\Colony;
use yii\test\ActiveFixture;

/**
 * Colony fixture. Data file is supplied per-suite via the Cest's _fixtures().
 */
class ColonyFixture extends ActiveFixture
{
    public $modelClass = Colony::class;
}

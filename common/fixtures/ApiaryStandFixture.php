<?php

declare(strict_types=1);

namespace common\fixtures;

use common\models\ApiaryStand;
use yii\test\ActiveFixture;

/**
 * Apiary stand fixture. Follows the UserFixture pattern: the model class is
 * fixed here, the concrete data file is supplied per-suite via the Cest's
 * _fixtures() configuration.
 */
class ApiaryStandFixture extends ActiveFixture
{
    public $modelClass = ApiaryStand::class;
}

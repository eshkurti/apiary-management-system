<?php

declare(strict_types=1);

namespace common\fixtures;

use common\models\Treatment;
use yii\test\ActiveFixture;

/**
 * Treatment fixture. Rows are inserted verbatim (wartezeit_expiry is provided
 * directly in the data file rather than recalculated) so the withdrawal window
 * relative to a batch harvest date can be controlled precisely.
 */
class TreatmentFixture extends ActiveFixture
{
    public $modelClass = Treatment::class;
}

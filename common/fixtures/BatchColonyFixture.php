<?php

declare(strict_types=1);

namespace common\fixtures;

use common\models\BatchColony;
use yii\test\ActiveFixture;

/**
 * Batch ↔ colony pivot fixture, used to attach source colonies to the seeded
 * batches so the release-gate withdrawal/disease checks and the recall/disease
 * cascades have data to traverse.
 */
class BatchColonyFixture extends ActiveFixture
{
    public $modelClass = BatchColony::class;
}

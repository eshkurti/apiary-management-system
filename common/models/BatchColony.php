<?php

declare(strict_types=1);

namespace common\models;

use yii\db\ActiveRecord;

/**
 * BatchColony — the batch ↔ colony pivot row.
 *
 * The application normally writes this pivot with a raw INSERT (see
 * BatchController::actionHarvest); this thin ActiveRecord exists so the
 * relationship can be seeded and asserted through the standard ActiveFixture
 * and seeRecord() tooling in the test suite.
 *
 * @property int $batch_id
 * @property int $colony_id
 */
class BatchColony extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%batch_colony}}';
    }

    public static function primaryKey(): array
    {
        return ['batch_id', 'colony_id'];
    }

    public function rules(): array
    {
        return [
            [['batch_id', 'colony_id'], 'required'],
            [['batch_id', 'colony_id'], 'integer'],
        ];
    }
}

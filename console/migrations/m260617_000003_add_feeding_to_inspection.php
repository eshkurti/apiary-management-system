<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Adds feeding fields to the inspection table so a field beekeeper can record
 * whether supplementary feeding was applied during the visit, and how much.
 */
class m260617_000003_add_feeding_to_inspection extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn(
            '{{%inspection}}',
            'feeding_applied',
            $this->tinyInteger(1)->notNull()->defaultValue(0)->after('notes'),
        );
        $this->addColumn(
            '{{%inspection}}',
            'feeding_quantity',
            $this->string(50)->null()->after('feeding_applied')
                ->comment('e.g. 1.5 kg fondant, 2L syrup'),
        );
    }

    public function safeDown(): void
    {
        $this->dropColumn('{{%inspection}}', 'feeding_quantity');
        $this->dropColumn('{{%inspection}}', 'feeding_applied');
    }
}

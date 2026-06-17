<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Adds a nullable wholesale_price column to the product table.
 * When set, wholesale customers are charged this price instead of the
 * standard price (FR17 — wholesale account designation).
 */
class m260617_000002_add_wholesale_price_to_product extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn(
            '{{%product}}',
            'wholesale_price',
            $this->decimal(10, 2)->null()->after('price')
                ->comment('Optional wholesale price — NULL means use standard price'),
        );
    }

    public function safeDown(): void
    {
        $this->dropColumn('{{%product}}', 'wholesale_price');
    }
}

<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Adds a review_unpublished marker to the product table. When a released batch
 * is forced back to review_required because a source colony was disease-flagged
 * (AC-CO-02.5), every product that was live in the shop at that moment is both
 * unpublished and marked here. On re-release the head beekeeper restores exactly
 * those products — and only those — rather than blindly re-publishing every
 * product ever linked to the batch.
 */
class m260618_000003_add_review_unpublished_to_product extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn(
            '{{%product}}',
            'review_unpublished',
            $this->tinyInteger(1)->notNull()->defaultValue(0)
                ->comment('1 = pulled from shop by a batch review cascade; re-published on re-release')
                ->after('is_published'),
        );
    }

    public function safeDown(): void
    {
        $this->dropColumn('{{%product}}', 'review_unpublished');
    }
}

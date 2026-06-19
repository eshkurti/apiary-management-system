<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Adds a review_note column to the batch table. When a released batch is forced
 * back into review_required status because a source colony was disease-flagged
 * after release (AC-CO-02.5), this column records which colony triggered the
 * review so the head beekeeper can see the reason at a glance — and so the
 * explanation survives even if the disease flag is later cleared.
 */
class m260618_000002_add_review_note_to_batch extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn(
            '{{%batch}}',
            'review_note',
            $this->string(255)->null()
                ->comment('Why the batch was moved to review_required — AC-CO-02.5')
                ->after('status'),
        );
    }

    public function safeDown(): void
    {
        $this->dropColumn('{{%batch}}', 'review_note');
    }
}

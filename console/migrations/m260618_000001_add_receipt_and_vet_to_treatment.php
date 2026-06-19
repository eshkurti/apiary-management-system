<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Adds two Bestandsbuch fields to the treatment table that are required to
 * fully reproduce the official EU Reg. 2019/6 Art. 108(2) ledger:
 *
 *   receipt_number — Belegnummer from the pharmacy/supplier receipt (Art. 108(2)(d))
 *   veterinarian   — name and address of the prescribing/supervising vet (Art. 108(2)(g)),
 *                    only populated when a vet was actually involved.
 *
 * Both columns are nullable: existing treatment records pre-date these fields
 * and a vet is not involved in routine apiary treatments.
 */
class m260618_000001_add_receipt_and_vet_to_treatment extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn(
            '{{%treatment}}',
            'receipt_number',
            $this->string(100)->null()
                ->comment('Belegnummer — receipt/invoice number from the supplier — Art. 108(2)(d)')
                ->after('supplier_address'),
        );
        $this->addColumn(
            '{{%treatment}}',
            'veterinarian',
            $this->string(255)->null()
                ->comment('Name and address of the vet, if involved — Art. 108(2)(g)')
                ->after('receipt_number'),
        );
    }

    public function safeDown(): void
    {
        $this->dropColumn('{{%treatment}}', 'veterinarian');
        $this->dropColumn('{{%treatment}}', 'receipt_number');
    }
}

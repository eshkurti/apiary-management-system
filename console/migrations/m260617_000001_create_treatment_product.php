<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Creates the treatment_product reference table and seeds it with the
 * approved German veterinary products used against Varroa and Tracheenmilbe.
 *
 * The same substances are used for both treatment types in German practice,
 * so every product is seeded once per treatment_type.
 *
 * The treatment create form reads this table to populate the product dropdown
 * and to autofill the statutory withdrawal/duration/quantity/supplier fields
 * (EU Regulation 2019/6 Article 108(2)).
 */
class m260617_000001_create_treatment_product extends Migration
{
    public function safeUp(): void
    {
        $opts = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';

        $this->createTable('{{%treatment_product}}', [
            'id'                      => $this->primaryKey(),
            'treatment_type'          => $this->string(40)->notNull()->comment('varroa|tracheenmilbe'),
            'product_name'            => $this->string(150)->notNull(),
            'typical_withdrawal_days' => $this->integer()->notNull()->defaultValue(0),
            'typical_duration_days'   => $this->integer()->notNull()->defaultValue(1),
            'typical_quantity'        => $this->string(50)->null(),
            'supplier_name'           => $this->string(150)->null(),
            'supplier_address'        => $this->string(255)->null(),
        ], $opts);

        $this->createIndex('idx_treatment_product_type', '{{%treatment_product}}', 'treatment_type');

        // Approved products (substance, withdrawal, duration, quantity, supplier, address).
        // The same substances apply to both Varroa and Tracheenmilbe.
        $products = [
            ['Oxuvar 5.7%',        21, 1,  '50 ml',               'Andermatt BioVet AG', 'Stahlermatten 6, 6146 Grossdietwil, Switzerland'],
            ['Varroxal 0.71g/g',   0,  1,  'per package insert',  'Andermatt BioVet AG', 'Stahlermatten 6, 6146 Grossdietwil, Switzerland'],
            ['MAQS',               0,  7,  '2 strips',            'Véto-pharma',         '75 Rue de Lisbonne, 75008 Paris, France'],
            ['Formivar 60%',       0,  3,  '25 ml per colony',    'Andermatt BioVet AG', 'Stahlermatten 6, 6146 Grossdietwil, Switzerland'],
            ['Thymovar',           0,  42, '2 pads',              'Andermatt BioVet AG', 'Stahlermatten 6, 6146 Grossdietwil, Switzerland'],
            ['ApiLife VAR',        0,  28, '1 tablet',            'Chemicals Laif SpA',  'Via Statale 52, 22070 Guanzate CO, Italy'],
            ['Apivar',             56, 42, '2 strips',            'Véto-pharma',         '75 Rue de Lisbonne, 75008 Paris, France'],
            ['Milchsäure 15%',     0,  1,  '15 ml per colony',    'various suppliers — enter manually', ''],
            ["Dany's Bienenwohl",  0,  1,  'per package insert',  'Chemicals Laif SpA',  'Via Statale 52, 22070 Guanzate CO, Italy'],
        ];

        $rows = [];
        foreach (['varroa', 'tracheenmilbe'] as $type) {
            foreach ($products as $p) {
                $rows[] = [$type, $p[0], $p[1], $p[2], $p[3], $p[4], $p[5]];
            }
        }

        $this->batchInsert('{{%treatment_product}}', [
            'treatment_type', 'product_name', 'typical_withdrawal_days',
            'typical_duration_days', 'typical_quantity', 'supplier_name', 'supplier_address',
        ], $rows);
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%treatment_product}}');
    }
}

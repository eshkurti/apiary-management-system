<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Creates all 14 domain tables for Honigmanufaktur Lindenhof.
 *
 * Tables (in dependency order):
 *   1.  company_profile
 *   2.  apiary_stand
 *   3.  colony
 *   4.  colony_movement
 *   5.  inspection
 *   6.  treatment
 *   7.  batch
 *   8.  batch_colony
 *   9.  product
 *   10. customer
 *   11. order
 *   12. order_item
 *   13. order_stage_log
 *   14. customer_note
 */
class m260616_000002_create_domain_tables extends Migration
{
    public function safeUp(): void
    {
        $opts = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';

        // ── 1. company_profile ────────────────────────────────────────────
        // Single-row table holding the legal Tierhalter identity.
        // Used in Bestandsbuch export headers (EU Regulation 2019/6 Art. 108(2)(f)).
        $this->createTable('{{%company_profile}}', [
            'id'           => $this->primaryKey(),
            'company_name' => $this->string(150)->notNull(),
            'keeper_name'  => $this->string(150)->notNull()->comment('Tierhalter — legal responsible person under TAMG 2022'),
            'address'      => $this->string(255)->notNull(),
            'postcode'     => $this->string(20)->notNull(),
            'city'         => $this->string(100)->notNull(),
            'phone'        => $this->string(50)->null(),
            'email'        => $this->string(150)->null(),
            'updated_at'   => $this->integer()->notNull(),
            'updated_by'   => $this->integer()->notNull(),
        ], $opts);

        $this->addForeignKey('fk_company_updated_by', '{{%company_profile}}', 'updated_by', '{{%user}}', 'id');

        // ── 2. apiary_stand ───────────────────────────────────────────────
        // Physical registered location. Each stand has its own
        // Veterinäramt authority registration number (§ 1a BienSeuchV).
        $this->createTable('{{%apiary_stand}}', [
            'id'                   => $this->primaryKey(),
            'stand_code'           => $this->string(50)->notNull()->unique()->comment('e.g. LIN-S-001'),
            'name'                 => $this->string(150)->notNull()->comment('e.g. Schwarzwald Stand'),
            'latitude'             => $this->decimal(10, 7)->notNull(),
            'longitude'            => $this->decimal(10, 7)->notNull(),
            'landkreis'            => $this->string(100)->notNull(),
            'authority_reg_number' => $this->string(100)->notNull()->comment('Veterinäramt registration number per stand'),
            'is_active'            => $this->tinyInteger(1)->notNull()->defaultValue(1),
            'created_at'           => $this->integer()->notNull(),
            'updated_at'           => $this->integer()->notNull(),
            'created_by'           => $this->integer()->notNull(),
        ], $opts);

        $this->addForeignKey('fk_stand_created_by', '{{%apiary_stand}}', 'created_by', '{{%user}}', 'id');

        // ── 3. colony ─────────────────────────────────────────────────────
        // Individual bee colony. Linked to its current apiary stand.
        // Carries disease flag state and annual treatment compliance flags
        // (§§ 14–15 BienSeuchV).
        $this->createTable('{{%colony}}', [
            'id'                     => $this->primaryKey(),
            'colony_code'            => $this->string(50)->notNull()->unique()->comment('e.g. LIN-C-001 — globally unique'),
            'apiary_stand_id'        => $this->integer()->notNull()->comment('Current assigned stand'),
            'queen_year'             => $this->integer()->notNull()->comment('Year the current queen was introduced'),
            'status'                 => $this->string(20)->notNull()->defaultValue('active')->comment('active|inactive|lost'),
            'annual_varroa_treated'  => $this->tinyInteger(1)->null()->comment('§ 15 BienSeuchV — reset each calendar year'),
            'annual_trachea_treated' => $this->tinyInteger(1)->null()->comment('§ 14 BienSeuchV — reset each calendar year'),
            // Disease flag — set by headBeekeeper, blocks batch release
            'disease_flag'           => $this->tinyInteger(1)->notNull()->defaultValue(0),
            'disease_flag_note'      => $this->text()->null(),
            'disease_flag_set_at'    => $this->integer()->null(),
            'disease_flag_set_by'    => $this->integer()->null(),
            'disease_flag_cleared_at'=> $this->integer()->null(),
            'disease_flag_cleared_by'=> $this->integer()->null(),
            'disease_flag_resolution'=> $this->text()->null(),
            'created_at'             => $this->integer()->notNull(),
            'updated_at'             => $this->integer()->notNull(),
            'created_by'             => $this->integer()->notNull(),
        ], $opts);

        $this->addForeignKey('fk_colony_stand',        '{{%colony}}', 'apiary_stand_id',     '{{%apiary_stand}}', 'id');
        $this->addForeignKey('fk_colony_flag_set_by',  '{{%colony}}', 'disease_flag_set_by',  '{{%user}}', 'id', 'SET NULL');
        $this->addForeignKey('fk_colony_flag_clear_by','{{%colony}}', 'disease_flag_cleared_by','{{%user}}', 'id', 'SET NULL');
        $this->addForeignKey('fk_colony_created_by',   '{{%colony}}', 'created_by',           '{{%user}}', 'id');
        $this->createIndex('idx_colony_stand',  '{{%colony}}', 'apiary_stand_id');
        $this->createIndex('idx_colony_status', '{{%colony}}', 'status');

        // ── 4. colony_movement ────────────────────────────────────────────
        // Records when a colony moves between apiary stands.
        // Historical inspection and treatment records retain their original stand.
        $this->createTable('{{%colony_movement}}', [
            'id'            => $this->primaryKey(),
            'colony_id'     => $this->integer()->notNull(),
            'from_stand_id' => $this->integer()->notNull(),
            'to_stand_id'   => $this->integer()->notNull(),
            'movement_date' => $this->date()->notNull(),
            'notes'         => $this->text()->null(),
            'created_at'    => $this->integer()->notNull(),
            'created_by'    => $this->integer()->notNull(),
        ], $opts);

        $this->addForeignKey('fk_movement_colony',     '{{%colony_movement}}', 'colony_id',     '{{%colony}}',       'id', 'CASCADE');
        $this->addForeignKey('fk_movement_from_stand', '{{%colony_movement}}', 'from_stand_id', '{{%apiary_stand}}', 'id');
        $this->addForeignKey('fk_movement_to_stand',   '{{%colony_movement}}', 'to_stand_id',   '{{%apiary_stand}}', 'id');
        $this->addForeignKey('fk_movement_created_by', '{{%colony_movement}}', 'created_by',    '{{%user}}',         'id');
        $this->createIndex('idx_movement_colony', '{{%colony_movement}}', 'colony_id');

        // ── 5. inspection ─────────────────────────────────────────────────
        // Colony health observation. Inspector identity = created_by (BlameableBehavior).
        // The apiary_stand_id is stored at inspection time so historical records
        // remain accurate when the colony later moves to a different stand.
        $this->createTable('{{%inspection}}', [
            'id'                  => $this->primaryKey(),
            'colony_id'           => $this->integer()->notNull(),
            'apiary_stand_id'     => $this->integer()->notNull()->comment('Stand at time of inspection — preserved historically'),
            'inspection_date'     => $this->date()->notNull()->comment('Cannot be in the future'),
            'weather'             => $this->string(150)->null(),
            'brood_pattern_score' => $this->tinyInteger()->null()->comment('1–5 scale'),
            'queen_sighted'       => $this->tinyInteger(1)->notNull()->defaultValue(0),
            'disease_indicators'  => $this->string(255)->null(),
            'notes'               => $this->text()->null(),
            'created_at'          => $this->integer()->notNull(),
            'updated_at'          => $this->integer()->notNull(),
            'created_by'          => $this->integer()->notNull()->comment('IS the inspector — populated by BlameableBehavior'),
        ], $opts);

        $this->addForeignKey('fk_inspection_colony',     '{{%inspection}}', 'colony_id',       '{{%colony}}',       'id', 'CASCADE');
        $this->addForeignKey('fk_inspection_stand',      '{{%inspection}}', 'apiary_stand_id', '{{%apiary_stand}}', 'id');
        $this->addForeignKey('fk_inspection_created_by', '{{%inspection}}', 'created_by',      '{{%user}}',         'id');
        $this->createIndex('idx_inspection_colony',      '{{%inspection}}', 'colony_id');
        $this->createIndex('idx_inspection_colony_date', '{{%inspection}}', ['colony_id', 'inspection_date']);

        // ── 6. treatment ──────────────────────────────────────────────────
        // Veterinary treatment record. All fields map to EU Regulation 2019/6
        // Article 108(2) Bestandsbuch mandatory columns.
        // operator_name is free text (not a user FK) because the Veterinäramt
        // document requires a printed name string.
        $this->createTable('{{%treatment}}', [
            'id'                          => $this->primaryKey(),
            'colony_id'                   => $this->integer()->notNull(),
            'apiary_stand_id'             => $this->integer()->notNull()->comment('Stand at time of treatment — Art. 108(2)(d)'),
            'treatment_type'              => $this->string(40)->notNull()->comment('varroa|tracheenmilbe|other'),
            'product_name'                => $this->string(150)->notNull()->comment('Trade name of medicinal product'),
            'pharmaceutical_batch_number' => $this->string(100)->notNull()->comment('Charge number on product packaging — Art. 108(2)(b)'),
            'quantity_per_colony'         => $this->string(50)->notNull()->comment('e.g. 50 ml — Art. 108(2)(b)'),
            'supplier_name'               => $this->string(150)->notNull()->comment('Pharmacy or supplier name — Art. 108(2)(c)'),
            'supplier_address'            => $this->string(255)->notNull()->comment('Full supplier address — Art. 108(2)(c)'),
            'application_date'            => $this->date()->notNull()->comment('Art. 108(2)(a)'),
            'colonies_treated_at_stand'   => $this->integer()->notNull()->comment('Count of colonies treated at this stand — Art. 108(2)(e)'),
            'withdrawal_days'             => $this->integer()->notNull()->defaultValue(0)->comment('Wartezeit from product package insert'),
            'wartezeit_expiry'            => $this->date()->notNull()->comment('Calculated: application_date + withdrawal_days'),
            'treatment_duration_days'     => $this->integer()->notNull()->comment('Duration of treatment course — Art. 108(2)(i)'),
            'operator_name'               => $this->string(150)->notNull()->comment('Printed name for Bestandsbuch — free text'),
            'notes'                       => $this->text()->null(),
            'created_at'                  => $this->integer()->notNull(),
            'updated_at'                  => $this->integer()->notNull(),
            'created_by'                  => $this->integer()->notNull(),
        ], $opts);

        $this->addForeignKey('fk_treatment_colony',     '{{%treatment}}', 'colony_id',       '{{%colony}}',       'id', 'CASCADE');
        $this->addForeignKey('fk_treatment_stand',      '{{%treatment}}', 'apiary_stand_id', '{{%apiary_stand}}', 'id');
        $this->addForeignKey('fk_treatment_created_by', '{{%treatment}}', 'created_by',      '{{%user}}',         'id');
        $this->createIndex('idx_treatment_colony',      '{{%treatment}}', 'colony_id');
        $this->createIndex('idx_treatment_colony_date', '{{%treatment}}', ['colony_id', 'application_date']);
        $this->createIndex('idx_treatment_expiry',      '{{%treatment}}', 'wartezeit_expiry');

        // ── 7. batch ──────────────────────────────────────────────────────
        // Honey production lot. Linked to source colonies via batch_colony pivot.
        // The lot_number is auto-generated and immutable after creation.
        // Status: pending_release → released | review_required
        $this->createTable('{{%batch}}', [
            'id'                  => $this->primaryKey(),
            'lot_number'          => $this->string(50)->notNull()->unique()->comment('Auto-generated, immutable — HonigV § 3, LKV § 1'),
            'harvest_date'        => $this->date()->notNull(),
            'apiary_stand_id'     => $this->integer()->notNull()->comment('Stand at harvest time — stored permanently'),
            'harvest_quantity_kg' => $this->decimal(8, 2)->notNull()->comment('Extracted honey in kilograms'),
            'honey_variety'       => $this->string(100)->notNull()->comment('Sortenbezeichnung / Verkehrsbezeichnung — HonigV'),
            'water_content'       => $this->decimal(5, 2)->null()->comment('Percentage — HonigV limit: 20% standard, 23% Heidehonig'),
            'hmf'                 => $this->decimal(6, 2)->null()->comment('mg/kg — HonigV max 40'),
            'conductivity'        => $this->decimal(6, 3)->null()->comment('mS/cm'),
            'fill_date'           => $this->date()->null(),
            'container_size'      => $this->string(50)->null()->comment('e.g. 500g'),
            'packaged_unit_count' => $this->integer()->null(),
            'best_before_date'    => $this->date()->null(),
            'origin_statement'    => $this->string(255)->null()->comment('HonigV origin text for label'),
            'haccp_confirmed'     => $this->tinyInteger(1)->notNull()->defaultValue(0)->comment('HACCP process confirmation'),
            'status'              => $this->string(20)->notNull()->defaultValue('pending_release')->comment('pending_release|released|review_required'),
            'released_at'         => $this->integer()->null(),
            'released_by'         => $this->integer()->null(),
            'created_at'          => $this->integer()->notNull(),
            'updated_at'          => $this->integer()->notNull(),
            'created_by'          => $this->integer()->notNull(),
        ], $opts);

        $this->addForeignKey('fk_batch_stand',       '{{%batch}}', 'apiary_stand_id', '{{%apiary_stand}}', 'id');
        $this->addForeignKey('fk_batch_released_by', '{{%batch}}', 'released_by',     '{{%user}}',         'id', 'SET NULL');
        $this->addForeignKey('fk_batch_created_by',  '{{%batch}}', 'created_by',      '{{%user}}',         'id');
        $this->createIndex('idx_batch_status',     '{{%batch}}', 'status');
        $this->createIndex('idx_batch_lot_number', '{{%batch}}', 'lot_number');

        // ── 8. batch_colony ───────────────────────────────────────────────
        // Pivot: many-to-many between batch and colony.
        // Enables end-to-end recall traceability from order to source colony.
        $this->createTable('{{%batch_colony}}', [
            'batch_id'  => $this->integer()->notNull(),
            'colony_id' => $this->integer()->notNull(),
            'PRIMARY KEY (batch_id, colony_id)' => '',
        ], $opts);

        $this->addForeignKey('fk_bc_batch',  '{{%batch_colony}}', 'batch_id',  '{{%batch}}',  'id', 'CASCADE');
        $this->addForeignKey('fk_bc_colony', '{{%batch_colony}}', 'colony_id', '{{%colony}}', 'id', 'CASCADE');

        // ── 9. product ────────────────────────────────────────────────────
        // Shop product derived from a released batch.
        // Only batches with status = released can be linked.
        // Provenance fields are inherited from the batch at publish time.
        $this->createTable('{{%product}}', [
            'id'             => $this->primaryKey(),
            'batch_id'       => $this->integer()->notNull()->comment('Must be a released batch'),
            'name'           => $this->string(150)->notNull(),
            'description'    => $this->text()->null(),
            'price'          => $this->decimal(10, 2)->notNull(),
            'stock_quantity' => $this->integer()->notNull()->defaultValue(0),
            'is_published'   => $this->tinyInteger(1)->notNull()->defaultValue(0),
            'created_at'     => $this->integer()->notNull(),
            'updated_at'     => $this->integer()->notNull(),
            'created_by'     => $this->integer()->notNull(),
        ], $opts);

        $this->addForeignKey('fk_product_batch',      '{{%product}}', 'batch_id',   '{{%batch}}', 'id', 'RESTRICT');
        $this->addForeignKey('fk_product_created_by', '{{%product}}', 'created_by', '{{%user}}',  'id');
        $this->createIndex('idx_product_published', '{{%product}}', 'is_published');

        // ── 10. customer ──────────────────────────────────────────────────
        // CRM record for retail and wholesale buyers.
        // user_id links to the shop account; NULL for wholesale-only accounts
        // that have no portal login.
        $this->createTable('{{%customer}}', [
            'id'                => $this->primaryKey(),
            'user_id'           => $this->integer()->null()->unique()->comment('Links shop account to CRM — NULL for wholesale-only'),
            'name'              => $this->string(150)->notNull(),
            'email'             => $this->string(150)->notNull()->unique(),
            'phone'             => $this->string(50)->null(),
            'company'           => $this->string(150)->null(),
            'address'           => $this->string(255)->null(),
            'postcode'          => $this->string(20)->null(),
            'city'              => $this->string(100)->null(),
            'country'           => $this->string(100)->notNull()->defaultValue('Germany'),
            'is_wholesale'      => $this->tinyInteger(1)->notNull()->defaultValue(0),
            'min_order_quantity'=> $this->integer()->null()->comment('Enforced at checkout when is_wholesale = 1'),
            'is_active'         => $this->tinyInteger(1)->notNull()->defaultValue(1),
            'created_at'        => $this->integer()->notNull(),
            'updated_at'        => $this->integer()->notNull(),
        ], $opts);

        $this->addForeignKey('fk_customer_user', '{{%customer}}', 'user_id', '{{%user}}', 'id', 'SET NULL');
        $this->createIndex('idx_customer_active', '{{%customer}}', 'is_active');

        // ── 11. order ─────────────────────────────────────────────────────
        // Customer purchase. Status advances through four stages in sequence:
        // received → packed → shipped → delivered. Cancelled is a terminal state.
        $this->createTable('{{%order}}', [
            'id'               => $this->primaryKey(),
            'customer_id'      => $this->integer()->notNull(),
            'order_number'     => $this->string(50)->notNull()->unique()->comment('Auto-generated e.g. ORD-2026-0001'),
            'order_date'       => $this->date()->notNull(),
            'total_amount'     => $this->decimal(10, 2)->notNull(),
            'status'           => $this->string(20)->notNull()->defaultValue('received')->comment('received|packed|shipped|delivered|cancelled'),
            'shipping_address' => $this->string(500)->null()->comment('Snapshot at order confirmation time'),
            'notes'            => $this->text()->null(),
            'created_at'       => $this->integer()->notNull(),
            'updated_at'       => $this->integer()->notNull(),
        ], $opts);

        $this->addForeignKey('fk_order_customer', '{{%order}}', 'customer_id', '{{%customer}}', 'id', 'RESTRICT');
        $this->createIndex('idx_order_customer', '{{%order}}', 'customer_id');
        $this->createIndex('idx_order_status',   '{{%order}}', 'status');

        // ── 12. order_item ────────────────────────────────────────────────
        // Line item. Stores product_name and lot_number as permanent snapshots
        // so recall traceability works even if product or batch records change.
        $this->createTable('{{%order_item}}', [
            'id'           => $this->primaryKey(),
            'order_id'     => $this->integer()->notNull(),
            'product_id'   => $this->integer()->notNull(),
            'batch_id'     => $this->integer()->notNull()->comment('Snapshot FK — links order to source batch for recall'),
            'lot_number'   => $this->string(50)->notNull()->comment('Permanent snapshot — never recalculated'),
            'product_name' => $this->string(150)->notNull()->comment('Permanent snapshot'),
            'quantity'     => $this->integer()->notNull(),
            'unit_price'   => $this->decimal(10, 2)->notNull(),
            'line_total'   => $this->decimal(10, 2)->notNull(),
        ], $opts);

        $this->addForeignKey('fk_oi_order',   '{{%order_item}}', 'order_id',   '{{%order}}',   'id', 'CASCADE');
        $this->addForeignKey('fk_oi_product', '{{%order_item}}', 'product_id', '{{%product}}', 'id', 'RESTRICT');
        $this->addForeignKey('fk_oi_batch',   '{{%order_item}}', 'batch_id',   '{{%batch}}',   'id', 'RESTRICT');
        $this->createIndex('idx_oi_order', '{{%order_item}}', 'order_id');
        $this->createIndex('idx_oi_batch', '{{%order_item}}', 'batch_id');

        // ── 13. order_stage_log ───────────────────────────────────────────
        // Audit trail for each fulfilment stage transition.
        // Records who made each transition and when, satisfying NFR3.
        $this->createTable('{{%order_stage_log}}', [
            'id'          => $this->primaryKey(),
            'order_id'    => $this->integer()->notNull(),
            'from_status' => $this->string(20)->null()->comment('NULL on initial order creation'),
            'to_status'   => $this->string(20)->notNull(),
            'notes'       => $this->text()->null()->comment('Internal staff note'),
            'created_at'  => $this->integer()->notNull(),
            'created_by'  => $this->integer()->notNull(),
        ], $opts);

        $this->addForeignKey('fk_osl_order',      '{{%order_stage_log}}', 'order_id',   '{{%order}}', 'id', 'CASCADE');
        $this->addForeignKey('fk_osl_created_by', '{{%order_stage_log}}', 'created_by', '{{%user}}',  'id');
        $this->createIndex('idx_osl_order', '{{%order_stage_log}}', 'order_id');

        // ── 14. customer_note ─────────────────────────────────────────────
        // CRM communication notes on customer records.
        // Satisfies AC-EC-07.3.
        $this->createTable('{{%customer_note}}', [
            'id'          => $this->primaryKey(),
            'customer_id' => $this->integer()->notNull(),
            'note'        => $this->text()->notNull(),
            'created_at'  => $this->integer()->notNull(),
            'created_by'  => $this->integer()->notNull(),
        ], $opts);

        $this->addForeignKey('fk_cn_customer',   '{{%customer_note}}', 'customer_id', '{{%customer}}', 'id', 'CASCADE');
        $this->addForeignKey('fk_cn_created_by', '{{%customer_note}}', 'created_by',  '{{%user}}',     'id');
        $this->createIndex('idx_cn_customer', '{{%customer_note}}', 'customer_id');
    }

    public function safeDown(): void
    {
        // Drop in reverse dependency order
        $this->dropTable('{{%customer_note}}');
        $this->dropTable('{{%order_stage_log}}');
        $this->dropTable('{{%order_item}}');
        $this->dropTable('{{%order}}');
        $this->dropTable('{{%customer}}');
        $this->dropTable('{{%product}}');
        $this->dropTable('{{%batch_colony}}');
        $this->dropTable('{{%batch}}');
        $this->dropTable('{{%treatment}}');
        $this->dropTable('{{%inspection}}');
        $this->dropTable('{{%colony_movement}}');
        $this->dropTable('{{%colony}}');
        $this->dropTable('{{%apiary_stand}}');
        $this->dropTable('{{%company_profile}}');
    }
}

<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Seeds the company profile and realistic demo data.
 *
 * Demo data:
 *   - 1 company profile (Honigmanufaktur Lindenhof)
 *   - 6 apiary stands in Landkreis Hof
 *   - 12 colonies across the stands (2 per stand)
 *   - 12 inspections (one per colony)
 *   - 8 treatments (mix of cleared and active Wartezeit)
 *   - 2 batches (one released, one pending)
 *   - 1 published product
 *   - 3 customers (2 retail, 1 wholesale)
 *   - 2 orders with line items
 */
class m260616_000003_seed_demo_data extends Migration
{
    public function safeUp(): void
    {
        $now = time();

        // Find the first user in the database.
        // If no user exists yet (fresh local environment before registration),
        // we temporarily disable the FK check, seed with adminId = 0,
        // then re-enable it. In practice the administrator should register
        // first and then run this seed, but we handle the edge case gracefully.
        $adminId = (new \yii\db\Query())
            ->select('id')
            ->from('{{%user}}')
            ->orderBy(['id' => SORT_ASC])
            ->scalar();

        if (!$adminId) {
            // No users yet — disable FK checks temporarily for seeding
            $this->db->createCommand('SET FOREIGN_KEY_CHECKS = 0')->execute();
            $adminId = 0;
            $fkDisabled = true;
        } else {
            $adminId = (int)$adminId;
            $fkDisabled = false;
        }

        // ── Company profile ───────────────────────────────────────────────
        $this->insert('{{%company_profile}}', [
            'company_name' => 'Honigmanufaktur Lindenhof',
            'keeper_name'  => 'Jürgen Heym',
            'address'      => 'Lindenhofstraße 12',
            'postcode'     => '95028',
            'city'         => 'Hof',
            'phone'        => '+49 9281 45678',
            'email'        => 'info@honigmanufaktur-lindenhof.de',
            'updated_at'   => $now,
            'updated_by'   => $adminId,
        ]);

        // ── Apiary stands ─────────────────────────────────────────────────
        $stands = [
            ['LIN-S-001', 'Lindenhof North',    50.3214, 11.9112, 'Hof', 'BY-09475-001'],
            ['LIN-S-002', 'Schwarzwald Stand',  50.4012, 11.7820, 'Hof', 'BY-09475-002'],
            ['LIN-S-003', 'Eichenfeld Site',    50.2802, 12.0011, 'Hof', 'BY-09475-003'],
            ['LIN-S-004', 'Birkenwald Stand',   50.3501, 11.8440, 'Hof', 'BY-09475-004'],
            ['LIN-S-005', 'Rapsfeld Nord',      50.3890, 11.9560, 'Hof', 'BY-09475-005'],
            ['LIN-S-006', 'Heidekraut Stand',   50.2601, 11.8200, 'Hof', 'BY-09475-006'],
        ];

        foreach ($stands as $s) {
            $this->insert('{{%apiary_stand}}', [
                'stand_code'           => $s[0],
                'name'                 => $s[1],
                'latitude'             => $s[2],
                'longitude'            => $s[3],
                'landkreis'            => $s[4],
                'authority_reg_number' => $s[5],
                'is_active'            => 1,
                'created_at'           => $now,
                'updated_at'           => $now,
                'created_by'           => $adminId,
            ]);
        }

        // Get stand IDs
        $standIds = (new \yii\db\Query())
            ->select(['id', 'stand_code'])
            ->from('{{%apiary_stand}}')
            ->indexBy('stand_code')
            ->column();

        // ── Colonies — 2 per stand ────────────────────────────────────────
        $colonies = [
            // Stand 1 — Lindenhof North
            ['LIN-C-001', $standIds['LIN-S-001'], 2024, 'active'],
            ['LIN-C-002', $standIds['LIN-S-001'], 2023, 'active'],
            // Stand 2 — Schwarzwald
            ['LIN-C-003', $standIds['LIN-S-002'], 2024, 'active'],
            ['LIN-C-004', $standIds['LIN-S-002'], 2024, 'active'],
            // Stand 3 — Eichenfeld
            ['LIN-C-005', $standIds['LIN-S-003'], 2023, 'active'],
            ['LIN-C-006', $standIds['LIN-S-003'], 2024, 'active'],
            // Stand 4 — Birkenwald
            ['LIN-C-007', $standIds['LIN-S-004'], 2024, 'active'],
            ['LIN-C-008', $standIds['LIN-S-004'], 2023, 'active'],
            // Stand 5 — Rapsfeld Nord
            ['LIN-C-009', $standIds['LIN-S-005'], 2024, 'active'],
            ['LIN-C-010', $standIds['LIN-S-005'], 2024, 'active'],
            // Stand 6 — Heidekraut
            ['LIN-C-011', $standIds['LIN-S-006'], 2023, 'active'],
            ['LIN-C-012', $standIds['LIN-S-006'], 2024, 'active'],
        ];

        foreach ($colonies as $c) {
            $this->insert('{{%colony}}', [
                'colony_code'            => $c[0],
                'apiary_stand_id'        => $c[1],
                'queen_year'             => $c[2],
                'status'                 => $c[3],
                'annual_varroa_treated'  => null,
                'annual_trachea_treated' => null,
                'disease_flag'           => 0,
                'created_at'             => $now,
                'updated_at'             => $now,
                'created_by'             => $adminId,
            ]);
        }

        // Get colony IDs
        $colonyIds = (new \yii\db\Query())
            ->select(['id', 'colony_code'])
            ->from('{{%colony}}')
            ->indexBy('colony_code')
            ->column();

        // ── Inspections — one per colony ──────────────────────────────────
        $inspections = [
            [$colonyIds['LIN-C-001'], $standIds['LIN-S-001'], '2026-05-10', 'Sunny, 18°C', 5, 1, null,            'Strong colony, excellent brood pattern'],
            [$colonyIds['LIN-C-002'], $standIds['LIN-S-001'], '2026-05-10', 'Sunny, 18°C', 4, 1, null,            'Queen laying well'],
            [$colonyIds['LIN-C-003'], $standIds['LIN-S-002'], '2026-05-12', 'Cloudy, 14°C', 5, 1, null,           'Excellent build-up'],
            [$colonyIds['LIN-C-004'], $standIds['LIN-S-002'], '2026-05-12', 'Cloudy, 14°C', 4, 0, null,           'Queen not sighted but eggs present'],
            [$colonyIds['LIN-C-005'], $standIds['LIN-S-003'], '2026-05-14', 'Sunny, 20°C', 3, 1, 'Slight chalkbrood', 'Monitor at next inspection'],
            [$colonyIds['LIN-C-006'], $standIds['LIN-S-003'], '2026-05-14', 'Sunny, 20°C', 4, 1, null,            'Healthy, good honey stores'],
            [$colonyIds['LIN-C-007'], $standIds['LIN-S-004'], '2026-05-16', 'Windy, 12°C', 4, 1, null,            'Good'],
            [$colonyIds['LIN-C-008'], $standIds['LIN-S-004'], '2026-05-16', 'Windy, 12°C', 5, 1, null,            'Strong'],
            [$colonyIds['LIN-C-009'], $standIds['LIN-S-005'], '2026-05-18', 'Sunny, 22°C', 5, 1, null,            'Peak season, heavy flow'],
            [$colonyIds['LIN-C-010'], $standIds['LIN-S-005'], '2026-05-18', 'Sunny, 22°C', 4, 1, null,            'Good build-up'],
            [$colonyIds['LIN-C-011'], $standIds['LIN-S-006'], '2026-05-20', 'Cloudy, 16°C', 4, 1, null,           'Normal'],
            [$colonyIds['LIN-C-012'], $standIds['LIN-S-006'], '2026-05-20', 'Cloudy, 16°C', 5, 1, null,           'Very strong'],
        ];

        foreach ($inspections as $i) {
            $this->insert('{{%inspection}}', [
                'colony_id'           => $i[0],
                'apiary_stand_id'     => $i[1],
                'inspection_date'     => $i[2],
                'weather'             => $i[3],
                'brood_pattern_score' => $i[4],
                'queen_sighted'       => $i[5],
                'disease_indicators'  => $i[6],
                'notes'               => $i[7],
                'created_at'          => $now,
                'updated_at'          => $now,
                'created_by'          => $adminId,
            ]);
        }

        // ── Treatments ────────────────────────────────────────────────────
        // Colonies 1-6: Oxalic acid, withdrawal cleared (applied March 2026)
        // Colonies 7-8: Formic acid MAQS, still in withdrawal (applied June 2026)
        $treatments = [
            // Cleared — Wartezeit expired before May harvest
            [$colonyIds['LIN-C-001'], $standIds['LIN-S-001'], 'varroa',       'Oxuvar 5.7%', 'OX-2026-001', '50 ml', 'Pharmabiene GmbH', 'Bienenstraße 1, 12345 Berlin', '2026-03-10', 2, 21, 10, 'Jürgen Heym'],
            [$colonyIds['LIN-C-002'], $standIds['LIN-S-001'], 'varroa',       'Oxuvar 5.7%', 'OX-2026-001', '50 ml', 'Pharmabiene GmbH', 'Bienenstraße 1, 12345 Berlin', '2026-03-10', 2, 21, 10, 'Jürgen Heym'],
            [$colonyIds['LIN-C-003'], $standIds['LIN-S-002'], 'varroa',       'Oxuvar 5.7%', 'OX-2026-001', '50 ml', 'Pharmabiene GmbH', 'Bienenstraße 1, 12345 Berlin', '2026-03-15', 2, 21, 10, 'Hans Meier'],
            [$colonyIds['LIN-C-004'], $standIds['LIN-S-002'], 'varroa',       'Oxuvar 5.7%', 'OX-2026-001', '50 ml', 'Pharmabiene GmbH', 'Bienenstraße 1, 12345 Berlin', '2026-03-15', 2, 21, 10, 'Hans Meier'],
            [$colonyIds['LIN-C-005'], $standIds['LIN-S-003'], 'varroa',       'Oxuvar 5.7%', 'OX-2026-001', '50 ml', 'Pharmabiene GmbH', 'Bienenstraße 1, 12345 Berlin', '2026-03-20', 2, 21, 10, 'Lisa Bauer'],
            [$colonyIds['LIN-C-006'], $standIds['LIN-S-003'], 'varroa',       'Oxuvar 5.7%', 'OX-2026-001', '50 ml', 'Pharmabiene GmbH', 'Bienenstraße 1, 12345 Berlin', '2026-03-20', 2, 21, 10, 'Lisa Bauer'],
            // Still in withdrawal — applied June 2026
            [$colonyIds['LIN-C-007'], $standIds['LIN-S-004'], 'varroa',       'MAQS',        'FA-2026-002', '2 strips', 'Imker-Shop Bayern', 'Honigweg 5, 80000 München', '2026-06-01', 2, 30, 7, 'Hans Meier'],
            [$colonyIds['LIN-C-008'], $standIds['LIN-S-004'], 'varroa',       'MAQS',        'FA-2026-002', '2 strips', 'Imker-Shop Bayern', 'Honigweg 5, 80000 München', '2026-06-01', 2, 30, 7, 'Hans Meier'],
        ];

        foreach ($treatments as $t) {
            $expiry = date('Y-m-d', strtotime($t[8] . ' + ' . $t[10] . ' days'));
            $this->insert('{{%treatment}}', [
                'colony_id'                   => $t[0],
                'apiary_stand_id'             => $t[1],
                'treatment_type'              => $t[2],
                'product_name'                => $t[3],
                'pharmaceutical_batch_number' => $t[4],
                'quantity_per_colony'         => $t[5],
                'supplier_name'               => $t[6],
                'supplier_address'            => $t[7],
                'application_date'            => $t[8],
                'colonies_treated_at_stand'   => $t[9],
                'withdrawal_days'             => $t[10],
                'wartezeit_expiry'            => $expiry,
                'treatment_duration_days'     => $t[11],
                'operator_name'               => $t[12],
                'created_at'                  => $now,
                'updated_at'                  => $now,
                'created_by'                  => $adminId,
            ]);
        }

        // ── Batches ───────────────────────────────────────────────────────
        // Batch 1: Released — all gate checks pass
        $this->insert('{{%batch}}', [
            'lot_number'          => 'LIN-2026-001',
            'harvest_date'        => '2026-05-15',
            'apiary_stand_id'     => $standIds['LIN-S-001'],
            'harvest_quantity_kg' => 48.50,
            'honey_variety'       => 'Blütenhonig',
            'water_content'       => 17.8,
            'hmf'                 => 5.2,
            'conductivity'        => 0.32,
            'fill_date'           => '2026-05-20',
            'container_size'      => '500g',
            'packaged_unit_count' => 90,
            'best_before_date'    => '2028-05-20',
            'origin_statement'    => 'Honey from Germany (Bavaria, Landkreis Hof)',
            'haccp_confirmed'     => 1,
            'status'              => 'released',
            'released_at'         => $now - (86400 * 10),
            'released_by'         => $adminId,
            'created_at'          => $now,
            'updated_at'          => $now,
            'created_by'          => $adminId,
        ]);

        $this->insert('{{%batch_colony}}', ['batch_id' => 1, 'colony_id' => $colonyIds['LIN-C-001']]);
        $this->insert('{{%batch_colony}}', ['batch_id' => 1, 'colony_id' => $colonyIds['LIN-C-002']]);

        // Batch 2: Pending release — missing HACCP and label fields
        $this->insert('{{%batch}}', [
            'lot_number'          => 'LIN-2026-002',
            'harvest_date'        => '2026-06-01',
            'apiary_stand_id'     => $standIds['LIN-S-003'],
            'harvest_quantity_kg' => 32.00,
            'honey_variety'       => 'Waldhonig',
            'water_content'       => 18.4,
            'hmf'                 => null,
            'conductivity'        => null,
            'fill_date'           => null,
            'container_size'      => null,
            'packaged_unit_count' => null,
            'best_before_date'    => null,
            'origin_statement'    => null,
            'haccp_confirmed'     => 0,
            'status'              => 'pending_release',
            'released_at'         => null,
            'released_by'         => null,
            'created_at'          => $now,
            'updated_at'          => $now,
            'created_by'          => $adminId,
        ]);

        $this->insert('{{%batch_colony}}', ['batch_id' => 2, 'colony_id' => $colonyIds['LIN-C-005']]);
        $this->insert('{{%batch_colony}}', ['batch_id' => 2, 'colony_id' => $colonyIds['LIN-C-006']]);

        // ── Product — from released batch 1 ──────────────────────────────
        $this->insert('{{%product}}', [
            'batch_id'       => 1,
            'name'           => 'Lindenhof Wildflower Honey 500g',
            'description'    => 'Pure Blütenhonig harvested in May 2026 from our Lindenhof North apiary. Smooth, mild, and naturally sweet. Water content 17.8%.',
            'price'          => 9.90,
            'stock_quantity' => 85,
            'is_published'   => 1,
            'created_at'     => $now,
            'updated_at'     => $now,
            'created_by'     => $adminId,
        ]);

        // ── Customers ─────────────────────────────────────────────────────
        $customers = [
            [null, 'Anna Schmidt',   'anna.schmidt@example.de',    '+49 9281 11111', null,             'Marienstr. 12',  '95028', 'Hof',      0, null],
            [null, 'Markus Becker',  'markus.becker@example.de',   '+49 921 33333',  null,             'Goethestr. 7',   '95444', 'Bayreuth', 0, null],
            [null, 'Bäckerei Weber', 'kontakt@baeckerei-weber.de',  '+49 9281 22222', 'Bäckerei Weber', 'Hauptstr. 45',   '95030', 'Hof',      1, 10 ],
        ];

        foreach ($customers as $c) {
            $this->insert('{{%customer}}', [
                'user_id'            => $c[0],
                'name'               => $c[1],
                'email'              => $c[2],
                'phone'              => $c[3],
                'company'            => $c[4],
                'address'            => $c[5],
                'postcode'           => $c[6],
                'city'               => $c[7],
                'country'            => 'Germany',
                'is_wholesale'       => $c[8],
                'min_order_quantity' => $c[9],
                'is_active'          => 1,
                'created_at'         => $now,
                'updated_at'         => $now,
            ]);
        }

        $customerIds = (new \yii\db\Query())
            ->select(['id', 'email'])
            ->from('{{%customer}}')
            ->indexBy('email')
            ->column();

        // ── Orders ────────────────────────────────────────────────────────
        // Order 1 — Anna Schmidt, shipped
        $this->insert('{{%order}}', [
            'customer_id'      => $customerIds['anna.schmidt@example.de'],
            'order_number'     => 'ORD-2026-0001',
            'order_date'       => '2026-06-05',
            'total_amount'     => 19.80,
            'status'           => 'shipped',
            'shipping_address' => 'Anna Schmidt, Marienstr. 12, 95028 Hof, Germany',
            'created_at'       => $now,
            'updated_at'       => $now,
        ]);
        $this->insert('{{%order_item}}', [
            'order_id'     => 1,
            'product_id'   => 1,
            'batch_id'     => 1,
            'lot_number'   => 'LIN-2026-001',
            'product_name' => 'Lindenhof Wildflower Honey 500g',
            'quantity'     => 2,
            'unit_price'   => 9.90,
            'line_total'   => 19.80,
        ]);
        $this->insert('{{%order_stage_log}}', [
            'order_id'    => 1,
            'from_status' => null,
            'to_status'   => 'received',
            'notes'       => null,
            'created_at'  => $now - 86400 * 11,
            'created_by'  => $adminId,
        ]);
        $this->insert('{{%order_stage_log}}', [
            'order_id'    => 1,
            'from_status' => 'received',
            'to_status'   => 'packed',
            'notes'       => 'Packed and ready for collection',
            'created_at'  => $now - 86400 * 10,
            'created_by'  => $adminId,
        ]);
        $this->insert('{{%order_stage_log}}', [
            'order_id'    => 1,
            'from_status' => 'packed',
            'to_status'   => 'shipped',
            'notes'       => 'Dispatched via DHL',
            'created_at'  => $now - 86400 * 9,
            'created_by'  => $adminId,
        ]);

        // Order 2 — Bäckerei Weber, wholesale, received
        $this->insert('{{%order}}', [
            'customer_id'      => $customerIds['kontakt@baeckerei-weber.de'],
            'order_number'     => 'ORD-2026-0002',
            'order_date'       => '2026-06-10',
            'total_amount'     => 99.00,
            'status'           => 'received',
            'shipping_address' => 'Bäckerei Weber, Hauptstr. 45, 95030 Hof, Germany',
            'created_at'       => $now,
            'updated_at'       => $now,
        ]);
        $this->insert('{{%order_item}}', [
            'order_id'     => 2,
            'product_id'   => 1,
            'batch_id'     => 1,
            'lot_number'   => 'LIN-2026-001',
            'product_name' => 'Lindenhof Wildflower Honey 500g',
            'quantity'     => 10,
            'unit_price'   => 9.90,
            'line_total'   => 99.00,
        ]);
        $this->insert('{{%order_stage_log}}', [
            'order_id'    => 2,
            'from_status' => null,
            'to_status'   => 'received',
            'notes'       => null,
            'created_at'  => $now,
            'created_by'  => $adminId,
        ]);

        if (!empty($fkDisabled)) {
            $this->db->createCommand('SET FOREIGN_KEY_CHECKS = 1')->execute();
        }
    }

    public function safeDown(): void
    {
        $this->delete('{{%order_stage_log}}');
        $this->delete('{{%order_item}}');
        $this->delete('{{%order}}');
        $this->delete('{{%customer}}');
        $this->delete('{{%product}}');
        $this->delete('{{%batch_colony}}');
        $this->delete('{{%batch}}');
        $this->delete('{{%treatment}}');
        $this->delete('{{%inspection}}');
        $this->delete('{{%colony}}');
        $this->delete('{{%apiary_stand}}');
        $this->delete('{{%company_profile}}');
    }
}

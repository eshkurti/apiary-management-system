<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Seeds two demo orders so the dashboard and order board show meaningful data.
 *
 * Also sets the wholesale_price on the Wildflower Honey product so the
 * wholesale customer sees a discounted price in the shop.
 *
 * After this migration:
 *   - ORD-2026-0001: Max Mustermann (retail), 2× €9.90, status = shipped
 *   - ORD-2026-0002: Bäckerei König (wholesale), 10× €7.50, status = received
 */
class m260621_000004_seed_demo_orders extends Migration
{
    private const PRODUCT_ID       = 1;
    private const BATCH_ID         = 1;
    private const LOT_NUMBER       = 'LIN-2026-001';
    private const PRODUCT_NAME     = 'Lindenhof Wildflower Honey 500g';
    private const RETAIL_PRICE     = 9.90;
    private const WHOLESALE_PRICE  = 7.50;

    public function safeUp(): void
    {
        $now = time();

        // Set wholesale price on the product.
        $this->update('{{%product}}', ['wholesale_price' => self::WHOLESALE_PRICE], ['id' => self::PRODUCT_ID]);

        // Look up customer CRM ids.
        $customerIds = (new \yii\db\Query())
            ->select(['id', 'email'])
            ->from('{{%customer}}')
            ->where(['email' => ['customer@lindenhof.de', 'wholesale@lindenhof.de']])
            ->indexBy('email')
            ->column();

        // Look up admin user id for stage log created_by — fall back to 1.
        $adminId = (int) ((new \yii\db\Query())
            ->select('id')
            ->from('{{%user}}')
            ->where(['username' => 'admin'])
            ->scalar() ?: 1);

        // ── Order 1: Max Mustermann (retail), shipped ─────────────────────
        $this->insert('{{%order}}', [
            'customer_id'      => $customerIds['customer@lindenhof.de'],
            'order_number'     => 'ORD-2026-0001',
            'order_date'       => '2026-06-05',
            'total_amount'     => 2 * self::RETAIL_PRICE,
            'status'           => 'shipped',
            'shipping_address' => 'Max Mustermann, Bahnhofstr. 22, 95028 Hof, Germany',
            'created_at'       => $now - 86400 * 14,
            'updated_at'       => $now - 86400 * 12,
        ]);
        $retailOrderId = (int) $this->db->getLastInsertID();

        $this->insert('{{%order_item}}', [
            'order_id'     => $retailOrderId,
            'product_id'   => self::PRODUCT_ID,
            'batch_id'     => self::BATCH_ID,
            'lot_number'   => self::LOT_NUMBER,
            'product_name' => self::PRODUCT_NAME,
            'quantity'     => 2,
            'unit_price'   => self::RETAIL_PRICE,
            'line_total'   => 2 * self::RETAIL_PRICE,
        ]);

        $this->insert('{{%order_stage_log}}', [
            'order_id'    => $retailOrderId,
            'from_status' => null,
            'to_status'   => 'received',
            'notes'       => null,
            'created_at'  => $now - 86400 * 14,
            'created_by'  => $adminId,
        ]);
        $this->insert('{{%order_stage_log}}', [
            'order_id'    => $retailOrderId,
            'from_status' => 'received',
            'to_status'   => 'packed',
            'notes'       => 'Packed and ready for dispatch',
            'created_at'  => $now - 86400 * 13,
            'created_by'  => $adminId,
        ]);
        $this->insert('{{%order_stage_log}}', [
            'order_id'    => $retailOrderId,
            'from_status' => 'packed',
            'to_status'   => 'shipped',
            'notes'       => 'Dispatched via DHL',
            'created_at'  => $now - 86400 * 12,
            'created_by'  => $adminId,
        ]);

        // ── Order 2: Bäckerei König (wholesale), received ─────────────────
        $this->insert('{{%order}}', [
            'customer_id'      => $customerIds['wholesale@lindenhof.de'],
            'order_number'     => 'ORD-2026-0002',
            'order_date'       => '2026-06-12',
            'total_amount'     => 10 * self::WHOLESALE_PRICE,
            'status'           => 'received',
            'shipping_address' => 'Bäckerei König, Marktplatz 3, 95032 Hof, Germany',
            'created_at'       => $now - 86400 * 7,
            'updated_at'       => $now - 86400 * 7,
        ]);
        $wholesaleOrderId = (int) $this->db->getLastInsertID();

        $this->insert('{{%order_item}}', [
            'order_id'     => $wholesaleOrderId,
            'product_id'   => self::PRODUCT_ID,
            'batch_id'     => self::BATCH_ID,
            'lot_number'   => self::LOT_NUMBER,
            'product_name' => self::PRODUCT_NAME,
            'quantity'     => 10,
            'unit_price'   => self::WHOLESALE_PRICE,
            'line_total'   => 10 * self::WHOLESALE_PRICE,
        ]);
        $this->insert('{{%order_stage_log}}', [
            'order_id'    => $wholesaleOrderId,
            'from_status' => null,
            'to_status'   => 'received',
            'notes'       => 'Wholesale bulk order',
            'created_at'  => $now - 86400 * 7,
            'created_by'  => $adminId,
        ]);
    }

    public function safeDown(): void
    {
        // Revert wholesale price.
        $this->update('{{%product}}', ['wholesale_price' => null], ['id' => self::PRODUCT_ID]);

        $orderIds = (new \yii\db\Query())
            ->select('id')
            ->from('{{%order}}')
            ->where(['order_number' => ['ORD-2026-0001', 'ORD-2026-0002']])
            ->column();

        if (!empty($orderIds)) {
            $this->delete('{{%order_stage_log}}', ['order_id' => $orderIds]);
            $this->delete('{{%order_item}}',      ['order_id' => $orderIds]);
            $this->delete('{{%order}}',           ['id'       => $orderIds]);
        }
    }
}

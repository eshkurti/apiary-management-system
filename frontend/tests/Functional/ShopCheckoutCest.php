<?php

declare(strict_types=1);

namespace frontend\tests\Functional;

use common\fixtures\ApiaryStandFixture;
use common\fixtures\BatchFixture;
use common\fixtures\CustomerFixture;
use common\fixtures\OrderFixture;
use common\fixtures\OrderItemFixture;
use common\fixtures\ProductFixture;
use common\fixtures\UserFixture;
use common\models\Batch;
use common\models\Order;
use common\models\OrderItem;
use common\models\Product;
use frontend\tests\Support\FunctionalTester;
use Yii;

/**
 * Frontend journey: a customer browses a product, sees its lot traceability,
 * and checks out.
 *
 * Covers the happy path (a retail order that decrements stock and snapshots the
 * source batch's lot number onto the order line) and the two guarded failure
 * cases: a checkout submitted without a delivery address, and a wholesale
 * account whose cart is below its minimum order quantity. The product-detail
 * test also asserts the public traceability panel (lot number, variety, harvest
 * date, water content) demanded by the shop requirements.
 */
final class ShopCheckoutCest
{
    public function _fixtures(): array
    {
        // Cross-app fixture data lives under common/ so both suites share it.
        $data = \Yii::getAlias('@common/tests/Support/data/');

        return [
            'user' => [
                'class'    => UserFixture::class,
                'dataFile' => $data . 'shop_users.php',
            ],
            'apiaryStands' => [
                'class'    => ApiaryStandFixture::class,
                'dataFile' => $data . 'shop_apiary_stands.php',
            ],
            'batches' => [
                'class'    => BatchFixture::class,
                'dataFile' => $data . 'shop_batches.php',
            ],
            'products' => [
                'class'    => ProductFixture::class,
                'dataFile' => $data . 'shop_products.php',
            ],
            'customers' => [
                'class'    => CustomerFixture::class,
                'dataFile' => $data . 'shop_customers.php',
            ],
            // Empty fixtures: truncate the order tables before each test so
            // demo-seed rows (and orders from a prior test) cannot be mistaken
            // for the order under assertion.
            'orders' => [
                'class'    => OrderFixture::class,
                'dataFile' => $data . 'shop_orders.php',
            ],
            'orderItems' => [
                'class'    => OrderItemFixture::class,
                'dataFile' => $data . 'shop_order_items.php',
            ],
        ];
    }

    // ── 1. Product detail shows lot traceability ──────────────────────────

    public function productDetailShowsLotTraceability(FunctionalTester $I): void
    {
        $I->amOnRoute('shop/product', ['id' => 1]);
        $I->seeResponseCodeIsSuccessful();

        $I->see('LIN-2026-101');                          // lot number
        $I->see('Blütenhonig');                           // honey variety
        $I->see(Yii::$app->formatter->asDate('2026-05-01')); // harvest date
        $I->see('18.00');                                 // water content
    }

    // ── 2. Checkout decrements stock and snapshots the lot number ─────────

    public function checkoutDecrementsStockAndSnapshotsLotNumber(FunctionalTester $I): void
    {
        $I->amLoggedInAs($I->grabFixture('user', 'customer'));

        // Add 3 units of product 1 to the cart.
        $I->amOnRoute('shop/product', ['id' => 1]);
        $I->selectOption('quantity', '3');
        $I->click('Add to Cart');

        // Submit checkout with a valid delivery address.
        $I->amOnRoute('checkout/index');
        $I->fillField('Customer[name]', 'Retail Buyer');
        $I->fillField('Customer[address]', 'Honigweg 1');
        $I->fillField('Customer[postcode]', '95028');
        $I->fillField('Customer[city]', 'Hof');
        $I->click('Place order');

        // Stock dropped from 10 to 7.
        $I->seeRecord(Product::class, ['id' => 1, 'stock_quantity' => 7]);

        // Grab the most recently created order for this customer (not just any).
        $order = Order::find()
            ->where(['customer_id' => 1])
            ->orderBy(['id' => SORT_DESC])
            ->one();
        $I->assertNotNull($order, 'an order was created for the customer');

        // The order line snapshots the source batch lot number exactly — assert
        // against the batch's actual lot_number read back from the database.
        $item = OrderItem::find()->where(['order_id' => $order->id])->one();
        $I->assertNotNull($item, 'the order has a line item');
        $batch = Batch::findOne(1);
        $I->assertSame($batch->lot_number, $item->lot_number);
        $I->assertSame(3, (int) $item->quantity);
    }

    // ── 3. Checkout requires a delivery address ───────────────────────────

    public function checkoutRequiresDeliveryAddress(FunctionalTester $I): void
    {
        $I->amLoggedInAs($I->grabFixture('user', 'customer'));

        $I->amOnRoute('shop/product', ['id' => 1]);
        $I->selectOption('quantity', '1');
        $I->click('Add to Cart');

        // Submit checkout with an empty address.
        $I->amOnRoute('checkout/index');
        $I->fillField('Customer[address]', '');
        $I->fillField('Customer[postcode]', '');
        $I->fillField('Customer[city]', '');
        $I->click('Place order');

        // No order is created and a validation error is shown.
        $I->dontSeeRecord(Order::class, ['customer_id' => 1]);
        $I->seeValidationError('cannot be blank');
    }

    // ── 4. Wholesale minimum order quantity enforced ──────────────────────

    public function wholesaleMinimumEnforced(FunctionalTester $I): void
    {
        $I->amLoggedInAs($I->grabFixture('user', 'wholesale'));

        // Add 5 units of product 2 — below the 10-unit wholesale minimum.
        $I->amOnRoute('shop/product', ['id' => 2]);
        $I->selectOption('quantity', '5');
        $I->click('Add to Cart');

        // The wholesale account already has a complete address on file, so the
        // checkout reaches — and is stopped by — the minimum-quantity rule.
        $I->amOnRoute('checkout/index');
        $I->click('Place order');

        $I->dontSeeRecord(Order::class, ['customer_id' => 2]);
        $I->see('minimum'); // "a minimum of 10 units per order applies."
    }
}

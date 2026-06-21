<?php

declare(strict_types=1);

namespace backend\tests\Functional;

use backend\tests\Support\FunctionalTester;
use common\fixtures\ApiaryStandFixture;
use common\fixtures\BatchColonyFixture;
use common\fixtures\BatchFixture;
use common\fixtures\ColonyFixture;
use common\fixtures\CustomerFixture;
use common\fixtures\OrderFixture;
use common\fixtures\OrderItemFixture;
use common\fixtures\ProductFixture;
use common\fixtures\TreatmentFixture;
use common\fixtures\UserFixture;
use common\models\Batch;
use common\models\BatchColony;
use common\models\Colony;
use common\models\Product;
use common\models\Treatment;

/**
 * Backend journey: apiary stand → colony → treatment → harvest → release gate.
 *
 * Walks the full Production Management → Compliance path for the head office
 * admin: registering a stand and colony, recording a veterinary treatment (with
 * the automatic Wartezeit calculation), recording a harvest into a pending
 * batch, and then exercising the five-check release gate — both the failure
 * case where an active withdrawal period blocks release and the happy path
 * where every check passes. The final test follows the disease-flag cascade
 * that reaches out of Production Management into Compliance (a released batch is
 * forced back to review) and Ecommerce (its shop product is unpublished).
 *
 * The admin is user id 1, to whom the RBAC seed migration assigns the
 * `administrator` role; the login fixture is loaded so that user exists.
 */
final class HarvestToReleaseCest
{
    public function _fixtures(): array
    {
        // Cross-app fixture data lives under common/ so both suites share it.
        $data = \Yii::getAlias('@common/tests/Support/data/');

        return [
            // Admin user (id 1 → administrator role via the RBAC seed).
            'user' => [
                'class'    => UserFixture::class,
                'dataFile' => codecept_data_dir() . 'login_data.php',
            ],
            'apiaryStands' => [
                'class'    => ApiaryStandFixture::class,
                'dataFile' => $data . 'harvest_apiary_stands.php',
            ],
            'colonies' => [
                'class'    => ColonyFixture::class,
                'dataFile' => $data . 'harvest_colonies.php',
            ],
            'treatments' => [
                'class'    => TreatmentFixture::class,
                'dataFile' => $data . 'harvest_treatments.php',
            ],
            'batches' => [
                'class'    => BatchFixture::class,
                'dataFile' => $data . 'harvest_batches.php',
            ],
            'batchColony' => [
                'class'    => BatchColonyFixture::class,
                'dataFile' => $data . 'harvest_batch_colony.php',
            ],
            'products' => [
                'class'    => ProductFixture::class,
                'dataFile' => $data . 'harvest_products.php',
            ],
            'customers' => [
                'class'    => CustomerFixture::class,
                'dataFile' => $data . 'harvest_customers.php',
            ],
            // Order tables are seeded (and thereby truncated) so the recall
            // trace resolves to exactly the one fixture order.
            'orders' => [
                'class'    => OrderFixture::class,
                'dataFile' => $data . 'harvest_orders.php',
            ],
            'orderItems' => [
                'class'    => OrderItemFixture::class,
                'dataFile' => $data . 'harvest_order_items.php',
            ],
        ];
    }

    /**
     * Log in as the admin before every test using the existing login fixture.
     */
    public function _before(FunctionalTester $I): void
    {
        $I->amOnRoute('site/login');
        $I->fillField('Your Username', 'erau');
        $I->fillField('Your Password', 'password_0');
        $I->click('login-button');
        // Login lands on the admin dashboard, confirming the admin session.
        $I->see('Dashboard');
    }

    // ── 1. Register an apiary stand ───────────────────────────────────────

    public function adminCreatesApiaryStand(FunctionalTester $I): void
    {
        $I->amOnRoute('apiary-stand/create');
        $I->submitForm('form:not(.d-inline)', [
            'ApiaryStand[stand_code]'           => 'LIN-S-099',
            'ApiaryStand[name]'                 => 'Neuer Teststand',
            'ApiaryStand[latitude]'             => '50.5000',
            'ApiaryStand[longitude]'            => '11.5000',
            'ApiaryStand[landkreis]'            => 'Hof',
            'ApiaryStand[authority_reg_number]' => 'VET-HOF-099',
            'ApiaryStand[is_active]'            => 1,
        ]);

        $I->seeResponseCodeIsSuccessful();
        $I->see('registered');
        $I->seeRecord(\common\models\ApiaryStand::class, ['stand_code' => 'LIN-S-099']);
    }

    // ── 2. Register a colony at the stand ─────────────────────────────────

    public function adminRegistersColonyAtStand(FunctionalTester $I): void
    {
        $I->amOnRoute('colony/create');
        $I->submitForm('form:not(.d-inline)', [
            'Colony[colony_code]'     => 'LIN-C-099',
            'Colony[apiary_stand_id]' => 1,
            'Colony[queen_year]'      => 2025,
            'Colony[status]'          => 'active',
        ]);

        $I->seeResponseCodeIsSuccessful();
        $I->seeRecord(Colony::class, [
            'colony_code'     => 'LIN-C-099',
            'apiary_stand_id' => 1,
        ]);
    }

    // ── 3. Record a treatment, Wartezeit auto-calculated ──────────────────

    public function treatmentRecordedWithWartezeitCalculated(FunctionalTester $I): void
    {
        $I->amOnRoute('treatment/create');
        $I->submitForm('#treatment-form', [
            'Treatment[apiary_stand_id]'             => 1,
            'Treatment[colony_id]'                   => 2,
            'Treatment[treatment_type]'              => Treatment::TYPE_VARROA,
            'Treatment[product_name]'                => 'AMO Varroa',
            'Treatment[pharmaceutical_batch_number]' => 'CHG-NEW',
            'Treatment[application_date]'            => '2026-06-01',
            'Treatment[withdrawal_days]'             => 21,
            'Treatment[treatment_duration_days]'     => 1,
            'Treatment[quantity_per_colony]'         => '50 ml',
            'Treatment[supplier_name]'               => 'Imkereibedarf Hof',
            'Treatment[supplier_address]'            => 'Hauptstraße 1, 95028 Hof',
            'Treatment[operator_name]'               => 'erau',
        ]);

        $I->seeResponseCodeIsSuccessful();
        // application_date 2026-06-01 + 21 days withdrawal = 2026-06-22.
        $I->seeRecord(Treatment::class, [
            'colony_id'        => 2,
            'application_date' => '2026-06-01',
            'withdrawal_days'  => 21,
            'wartezeit_expiry' => '2026-06-22',
        ]);
    }

    // ── 4. Record a harvest → pending-release batch ───────────────────────

    public function harvestCreatesBatchInPendingRelease(FunctionalTester $I): void
    {
        $I->amOnRoute('batch/harvest');
        // colony_ids[] is rendered client-side from the stand selection, so it
        // is supplied directly here (the field name matches the controller).
        $I->submitForm('form:not(.d-inline)', [
            'Batch[apiary_stand_id]'     => 1,
            'Batch[harvest_date]'        => '2026-06-15',
            'Batch[harvest_quantity_kg]' => '30.00',
            'Batch[honey_variety]'       => 'Blütenhonig',
            'colony_ids'                 => [2],
        ]);

        $I->seeResponseCodeIsSuccessful();
        // Fixtures already hold LIN-2026-001..003, so the next lot is -004.
        $I->seeRecord(Batch::class, [
            'lot_number' => 'LIN-2026-004',
            'status'     => Batch::STATUS_PENDING_RELEASE,
        ]);

        /** @var Batch $batch */
        $batch = $I->grabRecord(Batch::class, ['lot_number' => 'LIN-2026-004']);
        $I->seeRecord(BatchColony::class, [
            'batch_id'  => $batch->id,
            'colony_id' => 2,
        ]);
    }

    // ── 5. Release gate blocked by an active Wartezeit ────────────────────

    public function releaseGateBlockedByActiveWartezeit(FunctionalTester $I): void
    {
        // Batch 2 sources colony 1, whose Wartezeit (until 2026-05-20) still
        // spans the 2026-05-01 harvest date.
        $I->amOnRoute('compliance/gate', ['id' => 2]);
        $I->seeResponseCodeIsSuccessful();
        $I->see('Release Gate');

        // The withdrawal check is shown as failing for colony LIN-C-001.
        $I->see('Treatment withdrawal cleared');
        $I->see('FAIL');
        $I->see('Blocked by: LIN-C-001');

        // No release control is offered while a check fails.
        $I->see('Release blocked');
        $I->dontSee('Release Batch for Sale');
    }

    // ── 6. Release succeeds when all five checks pass ─────────────────────

    public function releaseSucceedsWhenAllChecksPassed(FunctionalTester $I): void
    {
        // Batch 1 sources only colony 2 (cleared) and has every label / water /
        // HACCP field satisfied.
        $I->amOnRoute('compliance/gate', ['id' => 1]);
        $I->seeResponseCodeIsSuccessful();
        $I->see('PASS');
        $I->dontSee('FAIL');

        $I->click('Release Batch for Sale');

        $I->seeRecord(Batch::class, [
            'id'     => 1,
            'status' => Batch::STATUS_RELEASED,
        ]);

        /** @var Batch $batch */
        $batch = $I->grabRecord(Batch::class, ['id' => 1]);
        $I->assertNotNull($batch->released_at, 'released_at is recorded');
        $I->assertNotNull($batch->released_by, 'released_by is recorded');
    }

    // ── 7. Disease flag → batch review + product unpublished ──────────────

    public function diseaseFlagPutsReleasedBatchUnderReviewAndUnpublishesProduct(FunctionalTester $I): void
    {
        // Colony 2 is a source colony of the already-released batch 3, which
        // carries the published product 1.
        $I->amOnRoute('colony/view', ['id' => 2]);
        $I->fillField('note', 'Suspected American Foulbrood — sunken, perforated cell cappings observed.');
        $I->click('Confirm — Set Flag');

        // The released batch is forced back to review_required (AC-CO-02.5)…
        $I->seeRecord(Batch::class, [
            'id'     => 3,
            'status' => Batch::STATUS_REVIEW_REQUIRED,
        ]);

        // …and its published product is pulled from the shop.
        $I->seeRecord(Product::class, [
            'batch_id'           => 3,
            'is_published'       => 0,
            'review_unpublished' => 1,
        ]);
    }

    // ── 8. Create a shop product from the released batch ──────────────────

    public function headBeekeeperCreatesProductFromReleasedBatch(FunctionalTester $I): void
    {
        // Batch 3 is released and still has units available. The create form is
        // minimal when a batch_id is supplied: the batch is locked and the name
        // is generated from "honey variety — lot number", so only price, units
        // and the publish flag are submitted.
        $I->amOnRoute('product/create', ['batch_id' => 3]);
        $I->seeResponseCodeIsSuccessful();
        $I->submitForm('form:not(.d-inline)', [
            'Product[price]'          => '6.50',
            'Product[wholesale_price]' => '',
            'Product[stock_quantity]' => 5,
            'Product[is_published]'   => 1,
        ]);

        $I->seeResponseCodeIsSuccessful();

        // The product is created against batch 3, published, with the generated
        // name that carries the batch lot number.
        $expectedName = 'Blütenhonig — LIN-2026-003';
        $I->seeRecord(Product::class, [
            'name'         => $expectedName,
            'batch_id'     => 3,
            'is_published' => 1,
        ]);

        /** @var Product $product */
        $product = $I->grabRecord(Product::class, ['name' => $expectedName]);
        $I->assertStringContainsString('LIN-2026-003', $product->name);
    }

    // ── 9. Bestandsbuch CSV export for a stand ────────────────────────────

    public function bestandsbuchExportProducesCSVForStand(FunctionalTester $I): void
    {
        $I->amOnRoute('compliance/bestandsbuch');
        $I->seeResponseCodeIsSuccessful();

        // The export is a GET form; the CSV button submits format=csv. A wide
        // date range covers both fixture treatments at stand 1.
        $I->submitForm('form:not(.d-inline)', [
            'stand_id'  => 1,
            'date_from' => '2026-01-01',
            'date_to'   => '2026-12-31',
            'format'    => 'csv',
        ]);

        $I->seeResponseCodeIsSuccessful();

        // The CSV is streamed as a raw file response; inspect the body directly.
        $csv = $I->grabPageSource();
        $I->assertStringContainsString('AMO Varroa', $csv); // treatment product
        $I->assertStringContainsString('LIN-S-001', $csv);  // stand code
        $I->assertStringContainsString('LIN-C-001', $csv);  // colony code
    }

    // ── 10. Recall trace finds orders for a lot number ────────────────────

    public function recallTraceFindsOrdersForLotNumber(FunctionalTester $I): void
    {
        // The fixture order line snapshots lot LIN-2026-003 (batch 3); tracing
        // that lot must surface the order and its customer.
        $I->amOnRoute('compliance/recall');
        $I->seeResponseCodeIsSuccessful();
        $I->submitForm('form:not(.d-inline)', [
            'search_type' => 'lot',
            'term'        => 'LIN-2026-003',
        ]);

        $I->seeResponseCodeIsSuccessful();
        $I->see('Affected orders');
        $I->see('ORD-2026-9001'); // order reference from the fixture
        $I->see('Hans Müller');   // the customer who received the lot
    }
}

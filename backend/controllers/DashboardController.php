<?php

declare(strict_types=1);

namespace backend\controllers;

use common\models\Batch;
use common\models\Colony;
use common\models\Order;
use common\models\Product;
use common\models\Treatment;
use yii\filters\AccessControl;
use yii\web\Controller;

/**
 * Operations dashboard (US-EC-08).
 *
 * All figures are read live from the database on each request (AC-EC-08.5):
 *   - open orders by fulfilment stage,
 *   - published product stock with a low-stock highlight (< 10 units),
 *   - colonies currently within an active withdrawal period,
 *   - pending-release batches with their specific failing gate conditions.
 *
 * Gated by the viewDashboard permission.
 */
class DashboardController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['viewDashboard'],
                    ],
                ],
            ],
        ];
    }

    public function actionIndex(): string
    {
        // AC-EC-08.1 — open orders by stage
        $openOrders = [
            Order::STATUS_RECEIVED => (int) Order::find()->where(['status' => Order::STATUS_RECEIVED])->count(),
            Order::STATUS_PACKED   => (int) Order::find()->where(['status' => Order::STATUS_PACKED])->count(),
            Order::STATUS_SHIPPED  => (int) Order::find()->where(['status' => Order::STATUS_SHIPPED])->count(),
        ];

        // AC-EC-08.2 — published product stock, low-stock highlight (< 10)
        $products = Product::find()
            ->where(['is_published' => 1])
            ->with('batch')
            ->orderBy(['stock_quantity' => SORT_ASC])
            ->all();
        $lowStockCount = 0;
        foreach ($products as $p) {
            if ($p->stock_quantity < 10) {
                $lowStockCount++;
            }
        }

        // Published products that have completely sold through (stock = 0). Their
        // lot/traceability pages remain public on purpose (Change 6).
        $soldOutCount = (int) Product::find()
            ->where(['is_published' => 1, 'stock_quantity' => 0])
            ->count();

        // AC-EC-08.3 — colonies currently within an active withdrawal period
        $today = date('Y-m-d');
        $withdrawalColonies = Colony::find()
            ->where(['id' => Treatment::find()->select('colony_id')->where(['>', 'wartezeit_expiry', $today])])
            ->with('apiaryStand')
            ->orderBy(['colony_code' => SORT_ASC])
            ->all();

        // AC-EC-08.4 — pending-release batches with their failing gate conditions
        $pendingBatches = [];
        $batches = Batch::find()
            ->where(['status' => Batch::STATUS_PENDING_RELEASE])
            ->with('apiaryStand')
            ->orderBy(['harvest_date' => SORT_DESC])
            ->all();
        foreach ($batches as $batch) {
            $failing = [];
            foreach ($batch->getReleaseGateChecks() as $label => $check) {
                if (!$check['passed']) {
                    $failing[$label] = $check['reason'];
                }
            }
            $pendingBatches[] = ['batch' => $batch, 'failing' => $failing];
        }

        return $this->render('index', [
            'openOrders'         => $openOrders,
            'products'           => $products,
            'lowStockCount'      => $lowStockCount,
            'soldOutCount'       => $soldOutCount,
            'withdrawalColonies' => $withdrawalColonies,
            'pendingBatches'     => $pendingBatches,
        ]);
    }
}

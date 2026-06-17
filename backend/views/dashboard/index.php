<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var array<string,int> $openOrders */
/** @var common\models\Product[] $products */
/** @var int $lowStockCount */
/** @var common\models\Colony[] $withdrawalColonies */
/** @var array<int, array{batch: common\models\Batch, failing: array<string,string>}> $pendingBatches */

use common\models\Order;
use yii\helpers\Html;

$this->title = 'Operations Dashboard';
$this->params['breadcrumbs'][] = $this->title;

$labels    = Order::statusLabels();
$openTotal = array_sum($openOrders);
?>
<h1 class="h3 mb-1">Operations Dashboard</h1>
<p class="text-muted">Live operational overview · <?= Yii::$app->formatter->asDatetime(time()) ?></p>

<!-- ── KPI cards ─────────────────────────────────────────────── -->
<div class="row g-3 mb-2">
    <div class="col-sm-6 col-xl-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase">Open Orders</div>
                <div class="display-6 fw-semibold"><?= $openTotal ?></div>
                <div class="small text-muted">
                    <?= $openOrders[Order::STATUS_RECEIVED] ?> received ·
                    <?= $openOrders[Order::STATUS_PACKED] ?> packed ·
                    <?= $openOrders[Order::STATUS_SHIPPED] ?> shipped
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase">Low Stock Products</div>
                <div class="display-6 fw-semibold <?= $lowStockCount > 0 ? 'text-danger' : '' ?>"><?= $lowStockCount ?></div>
                <div class="small text-muted">published products under 10 units</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase">Colonies in Wartezeit</div>
                <div class="display-6 fw-semibold"><?= count($withdrawalColonies) ?></div>
                <div class="small text-muted">within an active withdrawal period</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase">Batches Pending Release</div>
                <div class="display-6 fw-semibold"><?= count($pendingBatches) ?></div>
                <div class="small text-muted">awaiting release gate clearance</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- ── Pending batches + failing conditions ──────────────── -->
    <div class="col-xl-7">
        <div class="card shadow-sm h-100">
            <div class="card-header fw-semibold">Batches pending release — failing conditions</div>
            <div class="card-body">
                <?php if (empty($pendingBatches)): ?>
                    <p class="text-muted mb-0">No batches are pending release.</p>
                <?php else: ?>
                    <?php foreach ($pendingBatches as $row): ?>
                        <?php $batch = $row['batch']; ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <strong><?= Html::a(Html::encode($batch->lot_number), ['/compliance/gate', 'id' => $batch->id]) ?></strong>
                                <span class="text-muted small"><?= Html::encode($batch->honey_variety) ?> · <?= Html::encode($batch->harvest_date) ?></span>
                            </div>
                            <?php if (empty($row['failing'])): ?>
                                <div class="small text-success">All checks pass — ready to release.</div>
                            <?php else: ?>
                                <ul class="small mb-0 mt-1">
                                    <?php foreach ($row['failing'] as $label => $reason): ?>
                                        <li>
                                            <span class="text-danger fw-semibold"><?= Html::encode($label) ?>:</span>
                                            <?= Html::encode($reason) ?>
                                        </li>
                                    <?php endforeach ?>
                                </ul>
                            <?php endif ?>
                        </div>
                    <?php endforeach ?>
                <?php endif ?>
            </div>
        </div>
    </div>

    <!-- ── Colonies in withdrawal ────────────────────────────── -->
    <div class="col-xl-5">
        <div class="card shadow-sm h-100">
            <div class="card-header fw-semibold">Colonies in active withdrawal (Wartezeit)</div>
            <div class="card-body">
                <?php if (empty($withdrawalColonies)): ?>
                    <p class="text-muted mb-0">No colonies are currently within a withdrawal period.</p>
                <?php else: ?>
                    <table class="table table-sm table-striped mb-0">
                        <thead><tr><th>Colony</th><th>Stand</th><th>Wartezeit until</th></tr></thead>
                        <tbody>
                        <?php foreach ($withdrawalColonies as $colony): ?>
                            <tr>
                                <td><?= Html::a(Html::encode($colony->colony_code), ['/colony/stockkarte', 'id' => $colony->id]) ?></td>
                                <td><?= Html::encode($colony->apiaryStand->stand_code ?? '—') ?></td>
                                <td><?= Html::encode((string) $colony->getLatestWartezeitExpiry()) ?></td>
                            </tr>
                        <?php endforeach ?>
                        </tbody>
                    </table>
                <?php endif ?>
            </div>
        </div>
    </div>

    <!-- ── Published product stock ───────────────────────────── -->
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header fw-semibold">Published product stock</div>
            <div class="card-body">
                <?php if (empty($products)): ?>
                    <p class="text-muted mb-0">No published products.</p>
                <?php else: ?>
                    <table class="table table-striped align-middle mb-0">
                        <thead><tr><th>Product</th><th>Lot Number</th><th>Stock</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php foreach ($products as $product): ?>
                            <?php $low = $product->stock_quantity < 10; ?>
                            <tr>
                                <td><?= Html::a(Html::encode($product->name), ['/product/view', 'id' => $product->id]) ?></td>
                                <td><?= Html::encode($product->batch->lot_number ?? '—') ?></td>
                                <td class="<?= $low ? 'text-danger fw-semibold' : '' ?>"><?= (int) $product->stock_quantity ?></td>
                                <td>
                                    <?php if ($low): ?>
                                        <span class="lin-badge lin-badge--pending_release">Low stock</span>
                                    <?php else: ?>
                                        <span class="lin-badge lin-badge--released">OK</span>
                                    <?php endif ?>
                                </td>
                            </tr>
                        <?php endforeach ?>
                        </tbody>
                    </table>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>

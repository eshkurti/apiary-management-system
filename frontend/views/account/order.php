<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Order $order */

use common\models\Order;
use frontend\components\StatusBadge;
use yii\helpers\Html;

$this->title = 'Order ' . $order->order_number;
$this->params['breadcrumbs'][] = ['label' => 'My Account', 'url' => ['/account/index']];
$this->params['breadcrumbs'][] = ['label' => 'Orders', 'url' => ['/account/orders']];
$this->params['breadcrumbs'][] = $order->order_number;

$labels   = Order::statusLabels();
$sequence = [Order::STATUS_RECEIVED, Order::STATUS_PACKED, Order::STATUS_SHIPPED, Order::STATUS_DELIVERED];
$currentIndex = array_search($order->status, $sequence, true);
$cancelled = $order->status === Order::STATUS_CANCELLED;
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Order <?= Html::encode($order->order_number) ?></h1>
    <?= StatusBadge::html($order->status) ?>
</div>

<!-- Tracking progress (AC-EC-04.3) -->
<?php if (!$cancelled): ?>
    <div class="card mb-4">
        <div class="card-body py-4">
            <div class="order-tracker">
                <?php foreach ($sequence as $i => $stage): ?>
                    <?php $done = $currentIndex !== false && $i <= $currentIndex; ?>
                    <div class="stage <?= $done ? 'done' : 'todo' ?>">
                        <span class="dot"><?= $done ? '✓' : ($i + 1) ?></span>
                        <div class="label"><?= Html::encode($labels[$stage]) ?></div>
                    </div>
                <?php endforeach ?>
            </div>
        </div>
    </div>
<?php endif ?>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">Items</div>
            <table class="table mb-0 align-middle">
                <thead><tr><th>Product</th><th>Lot Number</th><th>Qty</th><th>Unit</th><th>Line Total</th></tr></thead>
                <tbody>
                <?php foreach ($order->items as $item): ?>
                    <tr>
                        <td><?= Html::encode($item->product_name) ?></td>
                        <td><span class="lot-chip"><?= Html::encode($item->lot_number) ?></span></td>
                        <td><?= (int) $item->quantity ?></td>
                        <td>€ <?= number_format((float) $item->unit_price, 2) ?></td>
                        <td>€ <?= number_format((float) $item->line_total, 2) ?></td>
                    </tr>
                <?php endforeach ?>
                </tbody>
                <tfoot>
                    <tr class="fw-bold"><td colspan="4" class="text-end">Total</td><td>€ <?= number_format((float) $order->total_amount, 2) ?></td></tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card mb-3">
            <div class="card-header">Delivery address</div>
            <div class="card-body">
                <div><?= nl2br(Html::encode((string) $order->shipping_address)) ?></div>
                <div class="text-muted small mt-2">Ordered <?= Yii::$app->formatter->asDate($order->order_date) ?></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Tracking history</div>
            <ul class="list-group list-group-flush">
                <?php foreach ($order->stageLog as $log): ?>
                    <?php $isNote = $log->from_status === $log->to_status; ?>
                    <?php if ($isNote) { continue; } // internal staff notes are not shown to customers ?>
                    <li class="list-group-item d-flex justify-content-between">
                        <span><?= Html::encode($labels[$log->to_status] ?? $log->to_status) ?></span>
                        <span class="text-muted small"><?= Yii::$app->formatter->asDate($log->created_at) ?></span>
                    </li>
                <?php endforeach ?>
            </ul>
        </div>
    </div>
</div>

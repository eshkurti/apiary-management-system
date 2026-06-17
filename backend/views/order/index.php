<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var array<string, common\models\Order[]> $columns */

use backend\components\StatusBadge;
use common\models\Order;
use yii\helpers\Html;

$this->title = 'Orders';
$this->params['breadcrumbs'][] = 'Ecommerce';
$this->params['breadcrumbs'][] = $this->title;

$labels = Order::statusLabels();
?>
<h1 class="h3 mb-3">Orders <small class="text-muted fs-6">— fulfilment board</small></h1>

<div class="row g-3">
    <?php foreach ($columns as $status => $orders): ?>
        <div class="col-md-6 col-xl-3">
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <?= StatusBadge::html($status) ?>
                    <span class="text-muted small"><?= count($orders) ?></span>
                </div>
                <div class="card-body d-flex flex-column gap-2">
                    <?php if (empty($orders)): ?>
                        <p class="text-muted small mb-0">No orders.</p>
                    <?php endif ?>
                    <?php foreach ($orders as $order): ?>
                        <?= Html::beginTag('a', [
                            'href' => \yii\helpers\Url::to(['view', 'id' => $order->id]),
                            'class' => 'text-decoration-none',
                        ]) ?>
                            <div class="border rounded-3 p-2 bg-white order-card">
                                <div class="d-flex justify-content-between">
                                    <span class="fw-semibold"><?= Html::encode($order->order_number) ?></span>
                                    <span class="fw-semibold">€ <?= number_format((float) $order->total_amount, 2) ?></span>
                                </div>
                                <div class="small text-muted"><?= Html::encode($order->customer->name ?? '—') ?></div>
                                <div class="small text-muted"><?= Html::encode($order->order_date) ?></div>
                            </div>
                        <?= Html::endTag('a') ?>
                    <?php endforeach ?>
                </div>
            </div>
        </div>
    <?php endforeach ?>
</div>

<?php
$this->registerCss('.order-card { transition: box-shadow .12s, border-color .12s; } '
    . '.order-card:hover { border-color: var(--lin-amber) !important; box-shadow: 0 2px 6px rgba(16,24,40,.08); }');
?>

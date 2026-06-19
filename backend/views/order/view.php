<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Order $model */

use backend\components\StatusBadge;
use common\models\Order;
use common\models\User;
use yii\helpers\Html;

$this->title = 'Order ' . $model->order_number;
$this->params['breadcrumbs'][] = ['label' => 'Orders', 'url' => ['index']];
$this->params['breadcrumbs'][] = $model->order_number;

$labels    = Order::statusLabels();
$next      = $model->getNextStatus();
$canCancel = in_array($model->status, [Order::STATUS_RECEIVED, Order::STATUS_PACKED], true);

/** Resolve a user id to a username, cached per request. */
$userName = static function (?int $id): string {
    if ($id === null) {
        return '—';
    }
    static $cache = [];
    if (!array_key_exists($id, $cache)) {
        $cache[$id] = User::findOne($id)->username ?? ('user #' . $id);
    }
    return $cache[$id];
};
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">
        <?= Html::encode($this->title) ?>
        <?= StatusBadge::html($model->status) ?>
    </h1>
    <div class="d-flex gap-2">
        <?php if ($next !== null): ?>
            <?= Html::beginForm(['advance', 'id' => $model->id], 'post', ['class' => 'd-inline']) ?>
            <?= Html::submitButton('Advance to “' . Html::encode($labels[$next]) . '” →', [
                'class' => 'btn btn-warning',
                'data' => ['confirm' => 'Advance order ' . $model->order_number . ' to ' . $labels[$next] . '?'],
            ]) ?>
            <?= Html::endForm() ?>
        <?php elseif ($model->status !== Order::STATUS_CANCELLED): ?>
            <span class="lin-badge lin-badge--delivered">Fulfilment complete</span>
        <?php endif ?>
        <?php if ($canCancel): ?>
            <?= Html::beginForm(['cancel', 'id' => $model->id], 'post', ['class' => 'd-inline']) ?>
            <?= Html::submitButton('Cancel Order', [
                'class' => 'btn btn-outline-danger',
                'data' => ['confirm' => 'Cancel order ' . $model->order_number . ' and restore its stock?'],
            ]) ?>
            <?= Html::endForm() ?>
        <?php endif ?>
    </div>
</div>

<div class="row">
    <div class="col-lg-7">
        <div class="card shadow-sm mb-3">
            <div class="card-header fw-semibold">Line items</div>
            <table class="table table-striped mb-0 align-middle">
                <thead>
                    <tr><th>Product</th><th>Lot Number</th><th>Qty</th><th>Unit</th><th>Line Total</th></tr>
                </thead>
                <tbody>
                <?php foreach ($model->items as $item): ?>
                    <tr>
                        <td><?= Html::encode($item->product_name) ?></td>
                        <td><?= Html::encode($item->lot_number) ?></td>
                        <td><?= (int) $item->quantity ?></td>
                        <td>€ <?= number_format((float) $item->unit_price, 2) ?></td>
                        <td>€ <?= number_format((float) $item->line_total, 2) ?></td>
                    </tr>
                <?php endforeach ?>
                </tbody>
                <tfoot>
                    <tr class="fw-semibold">
                        <td colspan="4" class="text-end">Total</td>
                        <td>€ <?= number_format((float) $model->total_amount, 2) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-header fw-semibold">Customer</div>
            <div class="card-body">
                <p class="mb-1">
                    <strong><?= Html::encode($model->customer->name ?? '—') ?></strong>
                    <?php if ($model->customer): ?>
                        <?= Html::a('view CRM', ['/customer/view', 'id' => $model->customer_id], ['class' => 'small ms-2']) ?>
                    <?php endif ?>
                </p>
                <div class="text-muted small"><?= nl2br(Html::encode((string) $model->shipping_address)) ?></div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card shadow-sm mb-3">
            <div class="card-header fw-semibold">Stage transition log</div>
            <ul class="list-group list-group-flush">
                <?php if (empty($model->stageLog)): ?>
                    <li class="list-group-item text-muted small">No history yet.</li>
                <?php endif ?>
                <?php foreach ($model->stageLog as $log): ?>
                    <?php $isNote = $log->from_status === $log->to_status; ?>
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between">
                            <span class="fw-semibold">
                                <?php if ($isNote): ?>
                                    Note
                                <?php else: ?>
                                    <?= Html::encode($log->from_status ? ($labels[$log->from_status] ?? $log->from_status) : 'Created') ?>
                                    → <?= Html::encode($labels[$log->to_status] ?? $log->to_status) ?>
                                <?php endif ?>
                            </span>
                            <span class="text-muted small"><?= Yii::$app->formatter->asDatetime($log->created_at) ?></span>
                        </div>
                        <?php if (!empty($log->notes)): ?>
                            <div class="small"><?= nl2br(Html::encode($log->notes)) ?></div>
                        <?php endif ?>
                        <div class="text-muted small">by <?= Html::encode($userName($log->created_by)) ?></div>
                    </li>
                <?php endforeach ?>
            </ul>
        </div>

        <div class="card shadow-sm">
            <div class="card-header fw-semibold">Add staff note</div>
            <div class="card-body">
                <?= Html::beginForm(['add-note', 'id' => $model->id], 'post') ?>
                <div class="mb-2">
                    <textarea name="note" class="form-control" rows="2" placeholder="Internal note — visible to staff only" required></textarea>
                </div>
                <?= Html::submitButton('Add Note', ['class' => 'btn btn-warning btn-sm']) ?>
                <?= Html::endForm() ?>
            </div>
        </div>
    </div>
</div>

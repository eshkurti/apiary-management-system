<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Customer $model */

use backend\components\StatusBadge;
use common\models\User;
use yii\helpers\Html;
use yii\widgets\DetailView;

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Customers', 'url' => ['index']];
$this->params['breadcrumbs'][] = $model->name;

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
        <?= $model->is_wholesale ? '<span class="lin-badge lin-badge--shipped align-middle">Wholesale</span>' : '' ?>
        <?= $model->is_active
            ? '<span class="lin-badge lin-badge--released align-middle">Active</span>'
            : '<span class="lin-badge lin-badge--cancelled align-middle">Inactive</span>' ?>
    </h1>
    <div>
        <?= Html::a('Edit', ['update', 'id' => $model->id], ['class' => 'btn btn-outline-secondary']) ?>
        <?php if ($model->is_active): ?>
            <?= Html::beginForm(['delete', 'id' => $model->id], 'post', ['class' => 'd-inline']) ?>
            <?= Html::submitButton('Deactivate', [
                'class' => 'btn btn-outline-danger',
                'data' => ['confirm' => 'Deactivate this customer? Order history is preserved.'],
            ]) ?>
            <?= Html::endForm() ?>
        <?php endif ?>
    </div>
</div>

<div class="row">
    <div class="col-lg-5">
        <div class="card shadow-sm mb-3">
            <div class="card-header fw-semibold">Details</div>
            <div class="card-body">
                <?= DetailView::widget([
                    'model' => $model,
                    'options' => ['class' => 'table table-striped detail-view mb-0'],
                    'attributes' => [
                        'name',
                        'email:email',
                        'phone',
                        ['attribute' => 'company', 'value' => $model->company ?: '—'],
                        'address',
                        ['label' => 'Location', 'value' => trim($model->postcode . ' ' . $model->city . ', ' . $model->country, ' ,')],
                        ['attribute' => 'is_wholesale', 'format' => 'boolean'],
                        [
                            'attribute' => 'min_order_quantity',
                            'value' => $model->min_order_quantity ? (string) $model->min_order_quantity . ' units' : '—',
                        ],
                    ],
                ]) ?>
            </div>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-header fw-semibold">Communication notes</div>
            <ul class="list-group list-group-flush">
                <?php if (empty($model->notes)): ?>
                    <li class="list-group-item text-muted small">No notes recorded.</li>
                <?php endif ?>
                <?php foreach ($model->notes as $note): ?>
                    <li class="list-group-item">
                        <div><?= nl2br(Html::encode($note->note)) ?></div>
                        <div class="text-muted small">
                            <?= Yii::$app->formatter->asDatetime($note->created_at) ?>
                            · by <?= Html::encode($userName($note->created_by)) ?>
                        </div>
                    </li>
                <?php endforeach ?>
            </ul>
            <div class="card-body">
                <?= Html::beginForm(['add-note', 'id' => $model->id], 'post') ?>
                <div class="mb-2">
                    <textarea name="note" class="form-control" rows="2" placeholder="Record a call, email or meeting…" required></textarea>
                </div>
                <?= Html::submitButton('Add Note', ['class' => 'btn btn-warning btn-sm']) ?>
                <?= Html::endForm() ?>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header fw-semibold">Order history</div>
            <table class="table table-striped mb-0 align-middle">
                <thead>
                    <tr><th>Order</th><th>Date</th><th>Status</th><th>Lot Numbers Supplied</th><th>Qty</th><th>Total</th></tr>
                </thead>
                <tbody>
                <?php if (empty($model->orders)): ?>
                    <tr><td colspan="6" class="text-muted">No orders yet.</td></tr>
                <?php endif ?>
                <?php foreach ($model->orders as $order): ?>
                    <?php
                    $lots = [];
                    $qty  = 0;
                    foreach ($order->items as $item) {
                        $lots[$item->lot_number] = true;
                        $qty += (int) $item->quantity;
                    }
                    ?>
                    <tr>
                        <td><?= Html::a(Html::encode($order->order_number), ['/order/view', 'id' => $order->id]) ?></td>
                        <td><?= Html::encode($order->order_date) ?></td>
                        <td><?= StatusBadge::html($order->status) ?></td>
                        <td class="small"><?= Html::encode(implode(', ', array_keys($lots))) ?: '—' ?></td>
                        <td><?= $qty ?></td>
                        <td>€ <?= number_format((float) $order->total_amount, 2) ?></td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

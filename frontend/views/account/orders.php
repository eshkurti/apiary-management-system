<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Order[] $orders */

use frontend\components\StatusBadge;
use yii\helpers\Html;

$this->title = 'Order History';
$this->params['breadcrumbs'][] = ['label' => 'My Account', 'url' => ['/account/index']];
$this->params['breadcrumbs'][] = 'Orders';
?>
<h1 class="h3 mb-3">Order History</h1>

<?php if (empty($orders)): ?>
    <div class="card"><div class="card-body text-center py-5">
        <p class="text-muted mb-3">You have not placed any orders yet.</p>
        <?= Html::a('Browse the shop', ['/shop/index'], ['class' => 'btn btn-honey']) ?>
    </div></div>
<?php else: ?>
    <div class="card">
        <table class="table align-middle mb-0">
            <thead>
                <tr><th>Order</th><th>Date</th><th>Items</th><th>Total</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($orders as $order): ?>
                <?php
                $itemCount = 0;
                foreach ($order->items as $it) {
                    $itemCount += (int) $it->quantity;
                }
                ?>
                <tr>
                    <td class="fw-semibold"><?= Html::encode($order->order_number) ?></td>
                    <td><?= Yii::$app->formatter->asDate($order->order_date) ?></td>
                    <td><?= $itemCount ?> item<?= $itemCount === 1 ? '' : 's' ?></td>
                    <td>€ <?= number_format((float) $order->total_amount, 2) ?></td>
                    <td><?= StatusBadge::html($order->status) ?></td>
                    <td class="text-end"><?= Html::a('View / track', ['/account/order', 'id' => $order->id], ['class' => 'btn btn-sm btn-outline-honey']) ?></td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
<?php endif ?>

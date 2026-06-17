<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\User $user */
/** @var common\models\Customer|null $customer */

use yii\helpers\Html;

$this->title = 'My Account';
$this->params['breadcrumbs'][] = 'My Account';
?>
<h1 class="h3 mb-3">My Account</h1>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">Account</div>
            <div class="card-body">
                <p class="mb-1"><strong><?= Html::encode($customer->name ?? $user->username) ?></strong></p>
                <p class="text-muted mb-2"><?= Html::encode($user->email) ?></p>
                <span class="status-badge status-delivered">Email verified</span>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">Orders</div>
            <div class="card-body d-flex flex-column justify-content-center">
                <p class="text-muted">View your order history and track current deliveries.</p>
                <?= Html::a('Order history & tracking →', ['/account/orders'], ['class' => 'btn btn-honey']) ?>
            </div>
        </div>
    </div>
</div>

<p class="mt-4">
    <?= Html::a('← Continue shopping', ['/shop/index'], ['class' => 'btn btn-outline-secondary']) ?>
</p>

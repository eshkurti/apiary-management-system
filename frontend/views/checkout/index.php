<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var array<int, array{product: common\models\Product, qty: int, unitPrice: float, isWholesale: bool, lineTotal: float}> $items */
/** @var float $total */
/** @var common\models\Customer $customer */

use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;

$this->title = 'Checkout';
$this->params['breadcrumbs'][] = ['label' => 'Cart', 'url' => ['/cart/index']];
$this->params['breadcrumbs'][] = 'Checkout';
?>
<h1 class="h3 mb-3">Checkout</h1>

<?php if ($customer->is_wholesale && $customer->min_order_quantity): ?>
    <div class="alert alert-info">
        Wholesale account — a minimum of <strong><?= (int) $customer->min_order_quantity ?></strong> units per order applies.
    </div>
<?php endif ?>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">Delivery address</div>
            <div class="card-body">
                <?php $form = ActiveForm::begin(); ?>

                <?= $form->field($customer, 'name')->textInput(['maxlength' => true])->label('Full name') ?>
                <?= $form->field($customer, 'address')->textInput(['maxlength' => true])->label('Street address') ?>

                <div class="row">
                    <div class="col-4"><?= $form->field($customer, 'postcode')->textInput(['maxlength' => true]) ?></div>
                    <div class="col-8"><?= $form->field($customer, 'city')->textInput(['maxlength' => true]) ?></div>
                </div>
                <div class="row">
                    <div class="col-6"><?= $form->field($customer, 'country')->textInput(['maxlength' => true]) ?></div>
                    <div class="col-6"><?= $form->field($customer, 'phone')->textInput(['maxlength' => true]) ?></div>
                </div>

                <div class="mt-3">
                    <?= Html::submitButton('Place order', ['class' => 'btn btn-honey btn-lg']) ?>
                </div>

                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">Order summary</div>
            <ul class="list-group list-group-flush">
                <?php foreach ($items as $item): ?>
                    <?php $p = $item['product']; ?>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>
                            <?= Html::encode($p->name) ?>
                            <span class="text-muted">× <?= $item['qty'] ?> @ € <?= number_format((float) $item['unitPrice'], 2) ?></span>
                            <?php if (!empty($item['isWholesale'])): ?>
                                <span class="badge bg-info text-dark">Wholesale</span>
                            <?php endif ?>
                            <div class="small text-muted">Lot <?= Html::encode($p->batch->lot_number ?? '—') ?></div>
                        </span>
                        <span>€ <?= number_format($item['lineTotal'], 2) ?></span>
                    </li>
                <?php endforeach ?>
                <li class="list-group-item d-flex justify-content-between fw-bold">
                    <span>Total</span>
                    <span>€ <?= number_format($total, 2) ?></span>
                </li>
            </ul>
        </div>
    </div>
</div>

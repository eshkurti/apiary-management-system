<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Product $product */
/** @var float $displayPrice */
/** @var bool $isWholesalePrice */

use yii\helpers\Html;
use yii\helpers\Url;

$displayPrice    ??= (float) $product->price;
$isWholesalePrice ??= false;

$this->title = $product->name;
$this->params['breadcrumbs'][] = ['label' => 'Shop', 'url' => ['/shop/index']];
$this->params['breadcrumbs'][] = $product->name;

$batch    = $product->batch;
$inStock  = $product->stock_quantity > 0;
$isGuest  = Yii::$app->user->isGuest;
$maxQty   = min(10, (int) $product->stock_quantity);
?>
<div class="row g-4">
    <div class="col-md-5">
        <div class="product-thumb rounded-3" style="height: 280px; font-size: 6rem;">🍯</div>
    </div>

    <div class="col-md-7">
        <span class="lot-chip mb-2 d-inline-block"><?= Html::encode($batch->lot_number ?? '—') ?></span>
        <h1 class="h3 mb-1"><?= Html::encode($product->name) ?></h1>
        <div class="text-muted mb-3"><?= Html::encode($batch->honey_variety ?? '') ?></div>

        <div class="price-tag mb-3">
            € <?= number_format((float) $displayPrice, 2) ?>
            <?php if ($isWholesalePrice): ?>
                <span class="badge bg-info text-dark align-middle">Wholesale price</span>
                <div class="small text-muted">Standard price € <?= number_format((float) $product->price, 2) ?></div>
            <?php endif ?>
        </div>

        <?php if ($product->description): ?>
            <p><?= nl2br(Html::encode($product->description)) ?></p>
        <?php endif ?>

        <p class="mb-3">
            <?php if ($inStock): ?>
                <span class="text-success fw-semibold"><?= (int) $product->stock_quantity ?> in stock</span>
            <?php else: ?>
                <span class="text-soldout">Sold out</span>
            <?php endif ?>
        </p>

        <?php if ($inStock): ?>
            <?php if ($isGuest): ?>
                <?= Html::a('Log in to buy', ['/site/login'], ['class' => 'btn btn-honey btn-lg']) ?>
                <p class="small text-muted mt-2">You need a verified account to add items to your cart.</p>
            <?php else: ?>
                <?= Html::beginForm(['/cart/add', 'id' => $product->id], 'post', ['class' => 'd-flex align-items-end gap-2']) ?>
                <div style="max-width: 7rem;">
                    <label class="form-label small">Quantity</label>
                    <select name="quantity" class="form-select">
                        <?php for ($q = 1; $q <= $maxQty; $q++): ?>
                            <option value="<?= $q ?>"><?= $q ?></option>
                        <?php endfor ?>
                    </select>
                </div>
                <?= Html::submitButton('Add to Cart', ['class' => 'btn btn-honey btn-lg']) ?>
                <?= Html::endForm() ?>
            <?php endif ?>
        <?php endif ?>
    </div>
</div>

<!-- Traceability panel (AC-EC-01.3) -->
<div class="trace-panel p-4 mt-4">
    <h2 class="h5 mb-3">🐝 Traceability</h2>
    <p class="text-muted small mb-3">Every jar carries the production record of its source batch.</p>
    <dl class="row mb-0">
        <dt class="col-sm-3">Lot Number (Losnummer)</dt>
        <dd class="col-sm-9"><?= Html::encode($batch->lot_number ?? '—') ?></dd>

        <dt class="col-sm-3">Honey Variety</dt>
        <dd class="col-sm-9"><?= Html::encode($batch->honey_variety ?? '—') ?></dd>

        <dt class="col-sm-3">Harvest Date</dt>
        <dd class="col-sm-9"><?= Html::encode((string) ($batch->harvest_date ?? '—')) ?></dd>

        <dt class="col-sm-3">Water Content</dt>
        <dd class="col-sm-9"><?= $batch && $batch->water_content !== null ? Html::encode($batch->water_content . ' %') : '—' ?></dd>

        <dt class="col-sm-3">Origin Statement</dt>
        <dd class="col-sm-9"><?= Html::encode($batch->origin_statement ?? '—') ?></dd>
    </dl>
</div>

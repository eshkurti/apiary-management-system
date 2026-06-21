<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var common\models\Customer|null $customer */

use common\models\Product;
use yii\bootstrap5\LinkPager;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Shop';

/** @var Product[] $products */
$products = $dataProvider->getModels();
?>
<section class="shop-hero p-4 p-lg-5 mb-4">
    <div class="col-lg-8">
        <h1 class="mb-2">Naturreiner Honig vom Lindenhof</h1>
        <p class="mb-0 fs-5">Honey harvested across six apiary stands in Landkreis Hof — every jar traceable from hive to lot number.</p>
    </div>
</section>

<?php if (empty($products)): ?>
    <div class="card"><div class="card-body text-center text-muted py-5">
        No honey is in stock right now. Please check back soon.
    </div></div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($products as $product): ?>
            <div class="col-sm-6 col-lg-4 col-xl-3">
                <a href="<?= Url::to(['/shop/product', 'id' => $product->id]) ?>" class="card product-card">
                    <div class="product-thumb">🍯</div>
                    <div class="card-body">
                        <div class="mb-2">
                            <span class="lot-chip"><?= Html::encode($product->batch->lot_number ?? '—') ?></span>
                        </div>
                        <h2 class="h6 mb-1"><?= Html::encode($product->name) ?></h2>
                        <div class="text-muted small mb-2"><?= Html::encode($product->batch->honey_variety ?? '') ?></div>
                        <div class="d-flex justify-content-between align-items-center">
                            <?php $isWholesalePrice = $product->isWholesalePriceFor($customer) ?>
                            <span class="price-tag">€ <?= number_format((float) $product->effectivePrice($customer), 2) ?>
                                <?php if ($customer !== null && $customer->is_wholesale && !$isWholesalePrice): ?>
                                    <small class="d-block text-muted fw-normal">Retail price</small>
                                <?php endif ?>
                            </span>
                            <span class="small text-muted"><?= (int) $product->stock_quantity ?> in stock</span>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach ?>
    </div>

    <div class="mt-4 d-flex justify-content-center">
        <?= LinkPager::widget(['pagination' => $dataProvider->pagination]) ?>
    </div>
<?php endif ?>

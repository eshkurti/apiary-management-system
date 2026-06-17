<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var array<int, array{product: common\models\Product, qty: int, unitPrice: float, isWholesale: bool, lineTotal: float}> $items */
/** @var float $total */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Your Cart';
$this->params['breadcrumbs'][] = ['label' => 'Shop', 'url' => ['/shop/index']];
$this->params['breadcrumbs'][] = 'Cart';
?>
<h1 class="h3 mb-3">Your Cart</h1>

<?php if (empty($items)): ?>
    <div class="card"><div class="card-body text-center py-5">
        <p class="text-muted mb-3">Your cart is empty.</p>
        <?= Html::a('Browse the shop', ['/shop/index'], ['class' => 'btn btn-honey']) ?>
    </div></div>
<?php else: ?>
    <div class="card mb-3">
        <table class="table align-middle mb-0">
            <thead>
                <tr><th>Product</th><th>Lot Number</th><th style="width: 9rem;">Quantity</th><th>Unit</th><th>Line Total</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <?php $p = $item['product']; ?>
                <tr>
                    <td>
                        <?= Html::a(Html::encode($p->name), ['/shop/product', 'id' => $p->id], ['class' => 'fw-semibold']) ?>
                        <div class="small text-muted"><?= Html::encode($p->batch->honey_variety ?? '') ?></div>
                    </td>
                    <td><span class="lot-chip"><?= Html::encode($p->batch->lot_number ?? '—') ?></span></td>
                    <td>
                        <?= Html::beginForm(['/cart/update', 'id' => $p->id], 'post', ['class' => 'd-flex gap-1']) ?>
                        <input type="number" name="quantity" value="<?= $item['qty'] ?>" min="0" max="<?= (int) $p->stock_quantity ?>" class="form-control form-control-sm" style="width: 4.5rem;">
                        <?= Html::submitButton('↻', ['class' => 'btn btn-sm btn-outline-honey', 'title' => 'Update quantity']) ?>
                        <?= Html::endForm() ?>
                    </td>
                    <td>
                        € <?= number_format((float) $item['unitPrice'], 2) ?>
                        <?php if (!empty($item['isWholesale'])): ?>
                            <span class="badge bg-info text-dark">Wholesale</span>
                        <?php endif ?>
                    </td>
                    <td>€ <?= number_format($item['lineTotal'], 2) ?></td>
                    <td class="text-end">
                        <?= Html::beginForm(['/cart/remove', 'id' => $p->id], 'post', ['class' => 'd-inline']) ?>
                        <?= Html::submitButton('Remove', ['class' => 'btn btn-sm btn-outline-secondary']) ?>
                        <?= Html::endForm() ?>
                    </td>
                </tr>
            <?php endforeach ?>
            </tbody>
            <tfoot>
                <tr class="fw-bold">
                    <td colspan="4" class="text-end">Order total</td>
                    <td colspan="2">€ <?= number_format($total, 2) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="d-flex justify-content-between">
        <?= Html::a('← Continue shopping', ['/shop/index'], ['class' => 'btn btn-outline-secondary']) ?>
        <?= Html::a('Proceed to checkout →', ['/checkout/index'], ['class' => 'btn btn-honey btn-lg']) ?>
    </div>
<?php endif ?>

<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */

use common\models\Product;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\helpers\Html;

$this->title = 'Products';
$this->params['breadcrumbs'][] = 'Ecommerce';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Products</h1>
    <?= Html::a('+ New Product', ['create'], ['class' => 'btn btn-warning']) ?>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'tableOptions' => ['class' => 'table table-striped table-hover align-middle'],
            'columns' => [
                'name',
                [
                    'label' => 'Lot Number',
                    'value' => static fn (Product $m): string => $m->batch->lot_number ?? '—',
                ],
                [
                    'attribute' => 'price',
                    'value' => static fn (Product $m): string => '€ ' . number_format((float) $m->price, 2),
                ],
                [
                    'attribute' => 'stock_quantity',
                    'format' => 'raw',
                    'value' => static fn (Product $m): string => $m->stock_quantity < 10
                        ? '<span class="text-danger fw-semibold">' . (int) $m->stock_quantity . '</span>'
                        : (string) (int) $m->stock_quantity,
                ],
                [
                    'attribute' => 'is_published',
                    'format' => 'raw',
                    'value' => static fn (Product $m): string => $m->is_published
                        ? '<span class="lin-badge lin-badge--released">Published</span>'
                        : '<span class="lin-badge lin-badge--default">Draft</span>',
                ],
                [
                    'class' => ActionColumn::class,
                    'template' => '{view} {update}',
                ],
            ],
        ]) ?>
    </div>
</div>

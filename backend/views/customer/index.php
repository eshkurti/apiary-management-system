<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */

use common\models\Customer;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\helpers\Html;

$this->title = 'Customers';
$this->params['breadcrumbs'][] = 'Ecommerce';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Customers</h1>
    <?= Html::a('+ New Customer', ['create'], ['class' => 'btn btn-warning']) ?>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'tableOptions' => ['class' => 'table table-striped table-hover align-middle'],
            'columns' => [
                'name',
                'email:email',
                [
                    'attribute' => 'company',
                    'value' => static fn (Customer $m): string => $m->company ?: '—',
                ],
                [
                    'label' => 'Orders',
                    'value' => static fn (Customer $m): int => $m->getOrderCount(),
                ],
                [
                    'attribute' => 'is_wholesale',
                    'format' => 'raw',
                    'value' => static fn (Customer $m): string => $m->is_wholesale
                        ? '<span class="lin-badge lin-badge--shipped">Wholesale</span>'
                        : '<span class="lin-badge lin-badge--default">Retail</span>',
                ],
                [
                    'attribute' => 'is_active',
                    'format' => 'raw',
                    'value' => static fn (Customer $m): string => $m->is_active
                        ? '<span class="lin-badge lin-badge--released">Active</span>'
                        : '<span class="lin-badge lin-badge--cancelled">Inactive</span>',
                ],
                [
                    'class' => ActionColumn::class,
                    'template' => '{view} {update}',
                ],
            ],
        ]) ?>
    </div>
</div>

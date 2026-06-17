<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */

use common\models\ApiaryStand;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\helpers\Html;

$this->title = 'Apiary Stands';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Apiary Stands <small class="text-muted fs-6">(Bienenstände)</small></h1>
    <?= Html::a('+ Register Stand', ['create'], ['class' => 'btn btn-warning']) ?>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'tableOptions' => ['class' => 'table table-striped table-hover align-middle'],
            'columns' => [
                'stand_code',
                'name',
                'landkreis',
                'authority_reg_number',
                [
                    'label' => 'Active Colonies',
                    'value' => static fn (ApiaryStand $m): int => $m->getActiveColonyCount(),
                ],
                [
                    'attribute' => 'is_active',
                    'format' => 'boolean',
                ],
                [
                    'class' => ActionColumn::class,
                    'template' => '{view} {update}',
                ],
            ],
        ]) ?>
    </div>
</div>

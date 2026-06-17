<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */

use common\models\Inspection;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\helpers\Html;

$this->title = 'Inspections';
$this->params['breadcrumbs'][] = $this->title;

$canLog = Yii::$app->user->can('logInspection');
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Inspections</h1>
    <?php if ($canLog): ?>
        <?= Html::a('+ Log Inspection', ['create'], ['class' => 'btn btn-warning']) ?>
    <?php endif ?>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'tableOptions' => ['class' => 'table table-striped table-hover align-middle'],
            'columns' => [
                'inspection_date',
                [
                    'label' => 'Colony',
                    'value' => static fn (Inspection $m): string => $m->colony->colony_code ?? '—',
                ],
                [
                    'label' => 'Stand',
                    'value' => static fn (Inspection $m): string => $m->apiaryStand->stand_code ?? '—',
                ],
                [
                    'attribute' => 'brood_pattern_score',
                    'label' => 'Brood',
                    'value' => static fn (Inspection $m): string => $m->brood_pattern_score ? $m->brood_pattern_score . '/5' : '—',
                ],
                [
                    'attribute' => 'queen_sighted',
                    'format' => 'boolean',
                ],
                'disease_indicators',
                [
                    'class' => ActionColumn::class,
                    'template' => '{view}',
                ],
            ],
        ]) ?>
    </div>
</div>

<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */

use backend\components\StatusBadge;
use common\models\Batch;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\helpers\Html;

$this->title = 'Batches';
$this->params['breadcrumbs'][] = $this->title;

$canHarvest  = Yii::$app->user->can('recordHarvest');
$canComplete = Yii::$app->user->can('completeBatchDetails');
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Batches <small class="text-muted fs-6">(Chargen / Lose)</small></h1>
    <?php if ($canHarvest): ?>
        <?= Html::a('+ Record Harvest', ['harvest'], ['class' => 'btn btn-warning']) ?>
    <?php endif ?>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'tableOptions' => ['class' => 'table table-striped table-hover align-middle'],
            'columns' => [
                'lot_number',
                'harvest_date',
                'honey_variety',
                [
                    'label' => 'Stand',
                    'value' => static fn (Batch $m): string => $m->apiaryStand->stand_code ?? '—',
                ],
                [
                    'attribute' => 'harvest_quantity_kg',
                    'value' => static fn (Batch $m): string => $m->harvest_quantity_kg . ' kg',
                ],
                [
                    'attribute' => 'status',
                    'format' => 'raw',
                    'value' => static fn (Batch $m): string => StatusBadge::html($m->status),
                ],
                [
                    // Act on a pending batch directly from the list (Change 5).
                    'label' => '',
                    'format' => 'raw',
                    'value' => static function (Batch $m) use ($canComplete): string {
                        if ($canComplete && $m->isPendingRelease()) {
                            return Html::a('Complete Details', ['update', 'id' => $m->id], [
                                'class' => 'btn btn-sm btn-warning',
                            ]);
                        }
                        return '';
                    },
                ],
                [
                    'class' => ActionColumn::class,
                    'template' => '{view}',
                ],
            ],
        ]) ?>
    </div>
</div>

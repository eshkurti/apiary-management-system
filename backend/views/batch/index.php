<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */

use backend\components\BatchStatusBadge;
use common\models\Batch;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\helpers\Html;

$this->title = 'Batches';
$this->params['breadcrumbs'][] = $this->title;

$canHarvest  = Yii::$app->user->can('recordHarvest');
$canComplete = Yii::$app->user->can('completeBatchDetails');
$canEvaluate = Yii::$app->user->can('evaluateReleaseGate');
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
                    'value' => static function (Batch $m): string {
                        $badge = BatchStatusBadge::html($m);
                        if ($m->status === Batch::STATUS_REVIEW_REQUIRED && $m->review_note) {
                            $badge .= '<div class="small text-danger mt-1">' . Html::encode($m->review_note) . '</div>';
                        }
                        return $badge;
                    },
                ],
                [
                    // Complete details on a pending batch directly from the list (Change 5).
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
                    // Read-only hand-off to Compliance for batches awaiting an
                    // (initial or post-review) release decision. The decision is
                    // made on the Release Gate page, never here.
                    'label' => '',
                    'format' => 'raw',
                    'value' => static function (Batch $m) use ($canEvaluate): string {
                        $awaiting = in_array(
                            $m->status,
                            [Batch::STATUS_PENDING_RELEASE, Batch::STATUS_REVIEW_REQUIRED],
                            true,
                        );
                        if ($canEvaluate && $awaiting) {
                            return Html::a('Go to Release Gate →', ['/compliance/gate', 'id' => $m->id], [
                                'class' => 'small',
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

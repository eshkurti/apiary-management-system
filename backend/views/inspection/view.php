<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Inspection $model */

use yii\helpers\Html;
use yii\widgets\DetailView;

$this->title = 'Inspection #' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Inspections', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<h1 class="h3 mb-3"><?= Html::encode($this->title) ?></h1>

<div class="card shadow-sm" style="max-width: 760px;">
    <div class="card-body">
        <?= DetailView::widget([
            'model' => $model,
            'options' => ['class' => 'table table-striped mb-0'],
            'attributes' => [
                [
                    'attribute' => 'colony_id',
                    'label' => 'Colony',
                    'format' => 'raw',
                    'value' => $model->colony
                        ? Html::a(Html::encode($model->colony->colony_code), ['/colony/view', 'id' => $model->colony_id])
                        : '—',
                ],
                [
                    'attribute' => 'apiary_stand_id',
                    'label' => 'Apiary Stand',
                    'value' => $model->apiaryStand->stand_code ?? '—',
                ],
                'inspection_date',
                'weather',
                [
                    'attribute' => 'brood_pattern_score',
                    'value' => $model->brood_pattern_score ? $model->brood_pattern_score . ' / 5' : '—',
                ],
                [
                    'attribute' => 'queen_sighted',
                    'format' => 'boolean',
                ],
                'disease_indicators',
                'notes:ntext',
                [
                    'label' => 'Inspector',
                    'value' => $model->inspector->username ?? '—',
                ],
                [
                    'label' => 'Recorded',
                    'value' => Yii::$app->formatter->asDatetime($model->created_at),
                ],
            ],
        ]) ?>
    </div>
</div>

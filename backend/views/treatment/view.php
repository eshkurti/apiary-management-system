<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Treatment $model */

use common\models\Treatment;
use yii\helpers\Html;
use yii\widgets\DetailView;

$this->title = 'Treatment #' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Treatments', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<h1 class="h3 mb-3"><?= Html::encode($this->title) ?></h1>

<?php if ($model->isInWithdrawal()): ?>
    <div class="alert alert-warning">
        Within the withdrawal period. Wartezeit expires
        <strong><?= Html::encode($model->wartezeit_expiry) ?></strong>.
    </div>
<?php endif ?>

<div class="card shadow-sm" style="max-width: 860px;">
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
                [
                    'attribute' => 'treatment_type',
                    'value' => Treatment::typeLabels()[$model->treatment_type] ?? $model->treatment_type,
                ],
                'product_name',
                'pharmaceutical_batch_number',
                'quantity_per_colony',
                'supplier_name',
                'supplier_address',
                'receipt_number',
                'veterinarian',
                'application_date',
                'colonies_treated_at_stand',
                'withdrawal_days',
                'wartezeit_expiry',
                'treatment_duration_days',
                'operator_name',
                'notes:ntext',
                [
                    'label' => 'Recorded',
                    'value' => Yii::$app->formatter->asDatetime($model->created_at),
                ],
            ],
        ]) ?>
    </div>
</div>

<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var common\models\Colony|null $colony */

use common\models\Treatment;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\helpers\Html;

$colony ??= null;
$this->title = 'Treatments';
$this->params['breadcrumbs'][] = $this->title;

$canRecord = Yii::$app->user->can('recordTreatment');
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Treatments <small class="text-muted fs-6">(Bestandsbuch)</small></h1>
    <?php if ($canRecord): ?>
        <?= Html::a('+ Record Treatment', ['create'], ['class' => 'btn btn-warning']) ?>
    <?php endif ?>
</div>

<?php if ($colony !== null): ?>
    <div class="alert alert-info d-flex justify-content-between align-items-center">
        <span>Showing treatments for colony <strong><?= Html::encode($colony->colony_code) ?></strong>.</span>
        <?= Html::a('Show all treatments', ['index'], ['class' => 'btn btn-sm btn-outline-secondary']) ?>
    </div>
<?php endif ?>

<div class="card shadow-sm">
    <div class="card-body">
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'tableOptions' => ['class' => 'table table-striped table-hover align-middle'],
            'columns' => [
                'application_date',
                [
                    'label' => 'Colony',
                    'value' => static fn (Treatment $m): string => $m->colony->colony_code ?? '—',
                ],
                [
                    'attribute' => 'treatment_type',
                    'value' => static fn (Treatment $m): string => Treatment::typeLabels()[$m->treatment_type] ?? $m->treatment_type,
                ],
                'product_name',
                'wartezeit_expiry',
                [
                    'label' => 'Status',
                    'format' => 'raw',
                    'value' => static fn (Treatment $m): string => $m->isInWithdrawal()
                        ? '<span class="badge bg-warning text-dark">In Wartezeit</span>'
                        : '<span class="badge bg-success">Cleared</span>',
                ],
                [
                    'class' => ActionColumn::class,
                    'template' => '{view}',
                ],
            ],
        ]) ?>
    </div>
</div>

<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Batch $model */

use backend\components\StatusBadge;
use yii\helpers\Html;
use yii\widgets\DetailView;

$this->title = 'Release Gate — ' . $model->lot_number;
$this->params['breadcrumbs'][] = ['label' => 'Release Gate', 'url' => ['release-gate']];
$this->params['breadcrumbs'][] = $model->lot_number;

$checks      = $model->getReleaseGateChecks();
$gatePassed  = $model->canBeReleased();
$canRelease  = Yii::$app->user->can('releaseBatch');
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">
        Release Gate — <?= Html::encode($model->lot_number) ?>
        <?= StatusBadge::html($model->status) ?>
    </h1>
    <?= Html::a('Batch details', ['/batch/view', 'id' => $model->id], ['class' => 'btn btn-outline-secondary']) ?>
</div>

<div class="row">
    <div class="col-lg-7">
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">Five-check release gate</div>
            <ul class="list-group list-group-flush">
                <?php foreach ($checks as $label => $check): ?>
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <span class="fw-semibold"><?= Html::encode($label) ?></span>
                            <?php if ($check['passed']): ?>
                                <span class="badge bg-success">PASS</span>
                            <?php else: ?>
                                <span class="badge bg-danger">FAIL</span>
                            <?php endif ?>
                        </div>
                        <div class="small text-muted"><?= Html::encode($check['reason']) ?></div>
                    </li>
                <?php endforeach ?>
            </ul>
            <div class="card-body">
                <?php if ($model->isReleased()): ?>
                    <div class="alert alert-success mb-0">
                        Released on <?= Yii::$app->formatter->asDatetime($model->released_at) ?>.
                    </div>
                <?php elseif ($gatePassed): ?>
                    <?php if ($canRelease): ?>
                        <?= Html::beginForm(['release', 'id' => $model->id], 'post') ?>
                        <?= Html::submitButton('Release Batch for Sale', [
                            'class' => 'btn btn-success w-100',
                            'data' => ['confirm' => 'Release batch ' . $model->lot_number . ' for sale?'],
                        ]) ?>
                        <?= Html::endForm() ?>
                    <?php else: ?>
                        <div class="alert alert-info mb-0">All checks pass. Awaiting release by the head beekeeper.</div>
                    <?php endif ?>
                <?php else: ?>
                    <button class="btn btn-secondary w-100" disabled>Release blocked — resolve failing checks above</button>
                <?php endif ?>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">Batch summary</div>
            <div class="card-body">
                <?= DetailView::widget([
                    'model' => $model,
                    'options' => ['class' => 'table table-striped mb-0'],
                    'attributes' => [
                        'lot_number',
                        'harvest_date',
                        [
                            'attribute' => 'apiary_stand_id',
                            'label' => 'Apiary Stand',
                            'value' => $model->apiaryStand->stand_code ?? '—',
                        ],
                        'honey_variety',
                        [
                            'attribute' => 'water_content',
                            'value' => $model->water_content !== null
                                ? $model->water_content . ' % (limit ' . $model->getWaterContentLimit() . ' %)'
                                : null,
                        ],
                        [
                            'attribute' => 'haccp_confirmed',
                            'format' => 'boolean',
                        ],
                    ],
                ]) ?>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">Source colonies</div>
            <ul class="list-group list-group-flush">
                <?php foreach ($model->colonies as $colony): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <?= Html::a(Html::encode($colony->colony_code), ['/colony/view', 'id' => $colony->id]) ?>
                        <span>
                            <?php if ($colony->disease_flag): ?>
                                <span class="badge bg-danger">disease flag</span>
                            <?php endif ?>
                            <?php if (!$colony->isWithdrawalCleared($model->harvest_date)): ?>
                                <span class="badge bg-warning text-dark">Wartezeit not cleared</span>
                            <?php else: ?>
                                <span class="badge bg-success">clear</span>
                            <?php endif ?>
                        </span>
                    </li>
                <?php endforeach ?>
            </ul>
        </div>
    </div>
</div>

<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Batch $model */

use backend\components\StatusBadge;
use yii\helpers\Html;
use yii\widgets\DetailView;

$this->title = 'Batch ' . $model->lot_number;
$this->params['breadcrumbs'][] = ['label' => 'Batches', 'url' => ['index']];
$this->params['breadcrumbs'][] = $model->lot_number;

$checks    = $model->getReleaseGateChecks();
$canRelease = Yii::$app->user->can('releaseBatch');
$canEdit    = Yii::$app->user->can('completeBatchDetails');
$gatePassed = $model->canBeReleased();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">
        <?= Html::encode($this->title) ?>
        <?= StatusBadge::html($model->status) ?>
    </h1>
    <div>
        <?php if ($canEdit && $model->isPendingRelease()): ?>
            <?= Html::a('Complete Details', ['update', 'id' => $model->id], ['class' => 'btn btn-outline-secondary']) ?>
        <?php endif ?>
    </div>
</div>

<div class="row">
    <div class="col-lg-7">
        <div class="card shadow-sm mb-3">
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
                        [
                            'attribute' => 'harvest_quantity_kg',
                            'value' => $model->harvest_quantity_kg . ' kg',
                        ],
                        'honey_variety',
                        [
                            'attribute' => 'water_content',
                            'value' => $model->water_content !== null
                                ? $model->water_content . ' % (limit ' . $model->getWaterContentLimit() . ' %)'
                                : null,
                        ],
                        'hmf',
                        'conductivity',
                        'fill_date',
                        'container_size',
                        'packaged_unit_count',
                        'best_before_date',
                        'origin_statement',
                        [
                            'attribute' => 'haccp_confirmed',
                            'format' => 'boolean',
                        ],
                    ],
                ]) ?>
            </div>
        </div>

        <div class="card shadow-sm mb-3">
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

    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">
                Release Gate <small class="text-muted">(Freigabeprüfung)</small>
            </div>
            <ul class="list-group list-group-flush">
                <?php foreach ($checks as $label => $check): ?>
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <span><?= Html::encode($label) ?></span>
                            <?php if ($check['passed']): ?>
                                <span class="badge bg-success">PASS</span>
                            <?php else: ?>
                                <span class="badge bg-danger">FAIL</span>
                            <?php endif ?>
                        </div>
                        <div class="small text-muted"><?= Html::encode($check['reason']) ?></div>
                        <?php if (!$check['passed']): ?>
                            <?php
                            // Direct navigation links so the head beekeeper can
                            // resolve each failing check immediately.
                            $editLink = Html::a(
                                '→ Complete batch details',
                                ['update', 'id' => $model->id],
                                ['class' => 'small'],
                            );
                            ?>
                            <div class="mt-1">
                                <?php switch ($check['type']):
                                    case 'withdrawal': ?>
                                        <?php foreach ($check['colonies'] as $c): ?>
                                            <div><?= Html::a(
                                                '→ View treatments for ' . Html::encode($c['code']),
                                                ['/treatment/index', 'colony_id' => $c['id']],
                                                ['class' => 'small'],
                                            ) ?></div>
                                        <?php endforeach ?>
                                        <?php break; ?>
                                    <?php case 'disease': ?>
                                        <?php foreach ($check['colonies'] as $c): ?>
                                            <div><?= Html::a(
                                                '→ Manage disease flag on ' . Html::encode($c['code']),
                                                ['/colony/view', 'id' => $c['id']],
                                                ['class' => 'small'],
                                            ) ?></div>
                                        <?php endforeach ?>
                                        <?php break; ?>
                                    <?php case 'water': ?>
                                    <?php case 'label': ?>
                                    <?php case 'haccp': ?>
                                        <?= $editLink ?>
                                        <?php break; ?>
                                <?php endswitch ?>
                            </div>
                        <?php endif ?>
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
                    <button class="btn btn-secondary w-100" disabled>Release blocked — resolve failing checks</button>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>

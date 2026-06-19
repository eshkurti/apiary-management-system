<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Batch $model */

use backend\components\BatchStatusBadge;
use backend\components\StatusBadge;
use common\models\Batch;
use common\models\User;
use yii\helpers\Html;
use yii\widgets\DetailView;

$this->title = 'Batch ' . $model->lot_number;
$this->params['breadcrumbs'][] = ['label' => 'Batches', 'url' => ['index']];
$this->params['breadcrumbs'][] = $model->lot_number;

$canEdit     = Yii::$app->user->can('completeBatchDetails');
$canEvaluate = Yii::$app->user->can('evaluateReleaseGate');
$canProducts = Yii::$app->user->can('manageProducts');

// Production-side allocation only — no compliance evaluation lives on this page.
$allocated    = $model->totalProductStock();   // sum of stock across all products (published or not)
$remaining    = $model->remainingUnits();       // packaged units − allocated
$soldThrough  = $model->isSoldThrough();
$hasUnitCount = $model->packaged_unit_count !== null;
// Shared eligibility — keeps this button in step with the Products → New
// selector and the server-side create guard (released, units left, not sold through).
$canCreate    = $canProducts && $hasUnitCount && $model->isAvailableForNewProduct();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">
        <?= Html::encode($this->title) ?>
        <?= BatchStatusBadge::html($model) ?>
    </h1>
    <div>
        <?php if ($canEdit && $model->isPendingRelease()): ?>
            <?= Html::a('Complete Details', ['update', 'id' => $model->id], ['class' => 'btn btn-outline-secondary']) ?>
        <?php endif ?>
        <?php if ($canCreate): ?>
            <?= Html::a('Create product from this batch →', ['/product/create', 'batch_id' => $model->id], ['class' => 'btn btn-warning']) ?>
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
        <!-- Release status: production status + a pointer to Compliance only.
             No gate evaluation/score is shown here — that lives in Compliance. -->
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">
                Release status <small class="text-muted">(Freigabe)</small>
            </div>
            <div class="card-body">
                <p class="mb-2"><?= StatusBadge::html($model->status) ?></p>
                <?php if ($model->isReleased()): ?>
                    <?php $releasedBy = $model->released_by ? User::findOne($model->released_by) : null; ?>
                    <div class="small text-muted">
                        Released on <?= Yii::$app->formatter->asDatetime($model->released_at) ?>
                        by <?= Html::encode($releasedBy->username ?? ('user #' . (int) $model->released_by)) ?>.
                    </div>
                    <?php if ($model->review_note): ?>
                        <div class="small text-muted mt-1">Audit trail: <?= Html::encode($model->review_note) ?></div>
                    <?php endif ?>
                <?php else: ?>
                    <?php if ($model->status === Batch::STATUS_REVIEW_REQUIRED && $model->review_note): ?>
                        <div class="small text-danger mb-2"><?= Html::encode($model->review_note) ?></div>
                    <?php endif ?>
                    <p class="text-muted small mb-2">The release decision is made in the Compliance module.</p>
                    <?php if ($canEvaluate): ?>
                        <?= Html::a('Go to Release Gate →', ['/compliance/gate', 'id' => $model->id], ['class' => 'btn btn-outline-primary w-100']) ?>
                    <?php endif ?>
                <?php endif ?>
            </div>
        </div>

        <!-- Batch allocation summary -->
        <div class="card shadow-sm mt-3">
            <div class="card-header bg-white fw-semibold">Batch allocation</div>
            <div class="card-body">
                <?php if (!$hasUnitCount): ?>
                    <p class="text-muted mb-0">Complete batch production details to see allocation.</p>
                <?php else: ?>
                    <?php if ($soldThrough): ?>
                        <div class="alert alert-secondary py-2 mb-3"><strong>This batch has sold through.</strong> All honey from this batch has been sold; the record stays intact for compliance and recall.</div>
                    <?php elseif ($remaining < 0): ?>
                        <div class="alert alert-danger py-2 mb-3"><strong>Over-allocated.</strong> Products claim more units than the batch yields — please investigate.</div>
                    <?php elseif ($remaining === 0): ?>
                        <div class="alert alert-warning py-2 mb-3"><strong>All units from this batch have been allocated to products.</strong> Stock is still available for sale.</div>
                    <?php endif ?>
                    <table class="table table-sm mb-0">
                        <tr>
                            <th>Total packaged units</th>
                            <td class="text-end"><?= (int) $model->packaged_unit_count ?></td>
                        </tr>
                        <tr>
                            <th>Allocated to products</th>
                            <td class="text-end"><?= $allocated ?></td>
                        </tr>
                        <tr>
                            <th>Remaining available</th>
                            <td class="text-end <?= $remaining < 0 ? 'text-danger fw-semibold' : '' ?>"><?= $remaining ?></td>
                        </tr>
                    </table>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>

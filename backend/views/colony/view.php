<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Colony $model */

use common\models\User;
use yii\helpers\Html;
use yii\widgets\DetailView;

$this->title = 'Colony ' . $model->colony_code;
$this->params['breadcrumbs'][] = ['label' => 'Colonies', 'url' => ['index']];
$this->params['breadcrumbs'][] = $model->colony_code;

$canManage      = Yii::$app->user->can('manageColonies');
$canMove        = Yii::$app->user->can('moveColony');
$canManageFlag  = Yii::$app->user->can('manageDiseaseFlag');
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0"><?= Html::encode($this->title) ?></h1>
    <div>
        <?= Html::a('📇 Stockkarte', ['stockkarte', 'id' => $model->id], ['class' => 'btn btn-outline-secondary']) ?>
        <?php if ($canMove): ?>
            <?= Html::a('Move', ['move', 'id' => $model->id], ['class' => 'btn btn-outline-secondary']) ?>
        <?php endif ?>
        <?php if ($canManage): ?>
            <?= Html::a('Edit', ['update', 'id' => $model->id], ['class' => 'btn btn-outline-secondary']) ?>
        <?php endif ?>
    </div>
</div>

<?php if (!$model->isWithdrawalCleared()): ?>
    <div class="alert alert-warning">
        <strong>Within Wartezeit.</strong> Honey is not eligible for harvest until
        <strong><?= Html::encode((string) $model->getLatestWartezeitExpiry()) ?></strong>.
    </div>
<?php endif ?>

<!-- Disease flag panel (US-CO-05) -->
<div class="card shadow-sm mb-3 <?= $model->disease_flag ? 'border-danger' : '' ?>" style="max-width: 720px;">
    <div class="card-header d-flex justify-content-between align-items-center <?= $model->disease_flag ? 'bg-danger text-white' : 'bg-white' ?>">
        <span class="fw-semibold">Disease concern (Krankheitsverdacht)</span>
        <?php if ($model->disease_flag): ?>
            <span class="badge bg-light text-danger">⚠ ACTIVE FLAG</span>
        <?php else: ?>
            <span class="badge bg-success">No active flag</span>
        <?php endif ?>
    </div>
    <div class="card-body">
        <?php if ($model->disease_flag): ?>
            <?php $setBy = $model->disease_flag_set_by ? User::findOne($model->disease_flag_set_by) : null; ?>
            <dl class="row mb-3">
                <dt class="col-sm-3 text-danger">Concern</dt>
                <dd class="col-sm-9 fw-semibold"><?= Html::encode((string) $model->disease_flag_note) ?></dd>
                <dt class="col-sm-3">Flagged on</dt>
                <dd class="col-sm-9"><?= $model->disease_flag_set_at ? Yii::$app->formatter->asDatetime($model->disease_flag_set_at) : '—' ?></dd>
                <dt class="col-sm-3">Set by</dt>
                <dd class="col-sm-9"><?= Html::encode($setBy->username ?? ('user #' . (int) $model->disease_flag_set_by)) ?></dd>
            </dl>
            <p class="text-muted small">This colony is blocked from contributing to any batch release, and any already-released batch sourcing it has been moved to <em>review required</em>.</p>
            <?php if ($canManageFlag): ?>
                <button class="btn btn-outline-success btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#clear-flag-form">Clear Flag</button>
                <div class="collapse mt-2" id="clear-flag-form">
                    <?= Html::beginForm(['clear-disease-flag', 'id' => $model->id], 'post', ['class' => 'card card-body bg-light']) ?>
                        <label class="form-label small" for="resolution">Resolution note (optional)</label>
                        <textarea class="form-control mb-2" id="resolution" name="resolution" rows="2" placeholder="e.g. Veterinary all-clear received, samples negative."></textarea>
                        <div><?= Html::submitButton('Confirm — Clear Flag', ['class' => 'btn btn-success btn-sm']) ?></div>
                    <?= Html::endForm() ?>
                </div>
            <?php endif ?>
        <?php else: ?>
            <p class="text-muted mb-2">No disease concern is currently active for this colony.</p>
            <?php if ($canManageFlag): ?>
                <button class="btn btn-outline-danger btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#set-flag-form">Set Disease Flag</button>
                <div class="collapse mt-2" id="set-flag-form">
                    <?= Html::beginForm(['set-disease-flag', 'id' => $model->id], 'post', ['class' => 'card card-body bg-light']) ?>
                        <label class="form-label small" for="note">Describe the disease concern <span class="text-danger">*</span></label>
                        <textarea class="form-control mb-2" id="note" name="note" rows="2" required placeholder="e.g. Suspected American Foulbrood — sunken, perforated cell cappings observed."></textarea>
                        <div><?= Html::submitButton('Confirm — Set Flag', ['class' => 'btn btn-danger btn-sm']) ?></div>
                    <?= Html::endForm() ?>
                </div>
            <?php endif ?>
        <?php endif ?>
    </div>
</div>

<div class="card shadow-sm" style="max-width: 720px;">
    <div class="card-body">
        <?= DetailView::widget([
            'model' => $model,
            'options' => ['class' => 'table table-striped mb-0'],
            'attributes' => [
                'colony_code',
                [
                    'attribute' => 'apiary_stand_id',
                    'label' => 'Current Apiary Stand',
                    'value' => static fn ($m): string => $m->apiaryStand
                        ? $m->apiaryStand->stand_code . ' — ' . $m->apiaryStand->name
                        : '—',
                ],
                'queen_year',
                [
                    'attribute' => 'status',
                    'value' => ucfirst($model->status),
                ],
                [
                    'attribute' => 'annual_varroa_treated',
                    'format' => 'boolean',
                ],
                [
                    'attribute' => 'annual_trachea_treated',
                    'format' => 'boolean',
                ],
                [
                    'label' => 'Registered',
                    'value' => Yii::$app->formatter->asDate($model->created_at),
                ],
            ],
        ]) ?>
    </div>
</div>

<?php if ($model->movements): ?>
    <h2 class="h5 mt-4">Movement history</h2>
    <table class="table table-sm table-striped" style="max-width: 720px;">
        <thead><tr><th>Date</th><th>From</th><th>To</th><th>Notes</th></tr></thead>
        <tbody>
        <?php foreach ($model->movements as $mv): ?>
            <tr>
                <td><?= Html::encode($mv->movement_date) ?></td>
                <td><?= Html::encode($mv->fromStand->stand_code ?? '—') ?></td>
                <td><?= Html::encode($mv->toStand->stand_code ?? '—') ?></td>
                <td><?= Html::encode((string) $mv->notes) ?></td>
            </tr>
        <?php endforeach ?>
        </tbody>
    </table>
<?php endif ?>

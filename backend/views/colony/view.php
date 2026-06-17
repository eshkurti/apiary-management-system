<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Colony $model */

use yii\helpers\Html;
use yii\widgets\DetailView;

$this->title = 'Colony ' . $model->colony_code;
$this->params['breadcrumbs'][] = ['label' => 'Colonies', 'url' => ['index']];
$this->params['breadcrumbs'][] = $model->colony_code;

$canManage = Yii::$app->user->can('manageColonies');
$canMove   = Yii::$app->user->can('moveColony');
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

<?php if ($model->disease_flag): ?>
    <div class="alert alert-danger">
        <strong>Active disease concern.</strong>
        <?= Html::encode((string) $model->disease_flag_note) ?>
        This colony is blocked from contributing to any batch release.
    </div>
<?php elseif (!$model->isWithdrawalCleared()): ?>
    <div class="alert alert-warning">
        <strong>Within Wartezeit.</strong> Honey is not eligible for harvest until
        <strong><?= Html::encode((string) $model->getLatestWartezeitExpiry()) ?></strong>.
    </div>
<?php endif ?>

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

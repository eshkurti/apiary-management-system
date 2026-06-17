<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Product $model */

use yii\helpers\Html;
use yii\widgets\DetailView;

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Products', 'url' => ['index']];
$this->params['breadcrumbs'][] = $model->name;

$batch = $model->batch;
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">
        <?= Html::encode($this->title) ?>
        <?= $model->is_published
            ? '<span class="lin-badge lin-badge--released align-middle">Published</span>'
            : '<span class="lin-badge lin-badge--default align-middle">Draft</span>' ?>
    </h1>
    <div>
        <?= Html::beginForm(['toggle-publish', 'id' => $model->id], 'post', ['class' => 'd-inline']) ?>
        <?= Html::submitButton(
            $model->is_published ? 'Unpublish' : 'Publish',
            ['class' => 'btn ' . ($model->is_published ? 'btn-outline-secondary' : 'btn-warning')],
        ) ?>
        <?= Html::endForm() ?>
        <?= Html::a('Edit', ['update', 'id' => $model->id], ['class' => 'btn btn-outline-secondary']) ?>
    </div>
</div>

<?php if (!$model->is_published && ($batch === null || !$batch->isReleased())): ?>
    <div class="alert alert-warning">This product is not linked to a released batch, so it cannot be published (AC-EC-05.1).</div>
<?php endif ?>

<div class="row">
    <div class="col-lg-6">
        <div class="card shadow-sm mb-3">
            <div class="card-header fw-semibold">Product</div>
            <div class="card-body">
                <?= DetailView::widget([
                    'model' => $model,
                    'options' => ['class' => 'table table-striped detail-view mb-0'],
                    'attributes' => [
                        'name',
                        [
                            'attribute' => 'price',
                            'value' => '€ ' . number_format((float) $model->price, 2),
                        ],
                        'stock_quantity',
                        'description:ntext',
                    ],
                ]) ?>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card shadow-sm mb-3">
            <div class="card-header fw-semibold">
                Inherited provenance <small class="text-muted">(read-only, from batch)</small>
            </div>
            <div class="card-body">
                <?php if ($batch === null): ?>
                    <p class="text-muted mb-0">No source batch linked.</p>
                <?php else: ?>
                    <table class="table table-striped detail-view mb-0">
                        <tr><th>Lot Number (Losnummer)</th><td><?= Html::encode($batch->lot_number) ?></td></tr>
                        <tr><th>Honey Variety</th><td><?= Html::encode($batch->honey_variety) ?></td></tr>
                        <tr><th>Harvest Date</th><td><?= Html::encode((string) $batch->harvest_date) ?></td></tr>
                        <tr><th>Water Content</th><td><?= $batch->water_content !== null ? Html::encode($batch->water_content . ' %') : '—' ?></td></tr>
                        <tr><th>Origin Statement</th><td><?= Html::encode((string) $batch->origin_statement) ?></td></tr>
                    </table>
                    <p class="mt-2 mb-0">
                        <?= Html::a('View batch ' . Html::encode($batch->lot_number), ['/batch/view', 'id' => $batch->id], ['class' => 'btn btn-sm btn-outline-secondary']) ?>
                    </p>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>

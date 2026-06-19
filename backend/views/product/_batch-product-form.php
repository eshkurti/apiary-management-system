<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Product $model */
/** @var common\models\Batch $batch */

use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;

// Shared, minimal product form for a locked source batch. Rendered both as a
// full page (create-from-batch.php) and injected via AJAX into the Products →
// New selector, so the experience is identical from either entry point.
$remaining = max(0, $batch->remainingUnits());
$container = (string) ($batch->container_size ?? '—');
?>
<div class="card shadow-sm" style="max-width: 720px;">
    <div class="card-body">

        <!-- Locked source batch + read-only context -->
        <div class="alert alert-light border">
            <div class="fw-semibold mb-1">Source batch (locked)</div>
            <div><?= Html::encode($batch->lot_number) ?> — <?= Html::encode($batch->honey_variety) ?></div>
            <div class="small text-muted mt-1">
                Harvested <?= Html::encode((string) $batch->harvest_date) ?>
                <?php if ($batch->water_content !== null): ?> · <?= Html::encode($batch->water_content . ' % water') ?><?php endif ?>
            </div>
            <div class="small text-muted">
                Product name will be: <strong><?= Html::encode($model->name) ?></strong>
                <span class="ms-1">(editable later on the product edit page)</span>
            </div>
        </div>

        <?php $form = ActiveForm::begin(['action' => ['create', 'batch_id' => $batch->id]]); ?>

        <div class="row">
            <div class="col-md-6"><?= $form->field($model, 'price')->textInput()->label('Price (€)') ?></div>
            <div class="col-md-6"><?= $form->field($model, 'wholesale_price')->textInput()
                ->label('Wholesale Price (€) — optional')
                ->hint('Leave blank to charge the standard price to all customers.') ?></div>
        </div>

        <div class="row">
            <div class="col-md-6"><?= $form->field($model, 'stock_quantity')->textInput([
                'type'  => 'number',
                'min'   => 1,
                'max'   => $remaining,
                'step'  => 1,
            ])->label('Units to list')
                ->hint('Between 1 and ' . $remaining . ' units remaining from this batch') ?></div>
            <div class="col-md-6">
                <label class="form-label">Container size (from batch)</label>
                <input type="text" class="form-control" value="<?= Html::encode($container) ?>" readonly>
                <div class="form-text">Set on the batch; read-only here.</div>
            </div>
        </div>

        <?= $form->field($model, 'is_published')->checkbox()->label('Publish in shop immediately') ?>

        <div class="mt-3">
            <?= Html::submitButton('Create Product', ['class' => 'btn btn-warning']) ?>
            <?= Html::a('Cancel', ['/batch/view', 'id' => $batch->id], ['class' => 'btn btn-outline-secondary']) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>

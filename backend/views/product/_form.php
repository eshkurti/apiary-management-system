<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Product $model */

use common\models\Batch;
use yii\bootstrap5\ActiveForm;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;

// AC-EC-05.1 — only released batches may be linked / published
$batchRecords = Batch::find()->where(['status' => Batch::STATUS_RELEASED])->orderBy(['lot_number' => SORT_ASC])->all();
$releasedBatches = ArrayHelper::map(
    $batchRecords,
    'id',
    static fn (Batch $b): string => $b->lot_number . ' — ' . $b->honey_variety,
);

// Remaining-units data per batch for the client-side stock hint / max — the
// units still unallocated across the batch's other products (excluding this one).
$batchYield = [];
foreach ($batchRecords as $b) {
    $max = $b->availableUnits() !== null ? max(0, $b->remainingUnits($model->id)) : null;
    $batchYield[$b->id] = [
        'container' => (string) ($b->container_size ?? ''),
        'maxUnits'  => $max,
    ];
}

$batch      = $model->batch; // inherited provenance, if a batch is already selected
$initialMax = ($batch !== null && $batch->availableUnits() !== null)
    ? max(0, $batch->remainingUnits($model->id))
    : null;
?>
<div class="card shadow-sm" style="max-width: 820px;">
    <div class="card-body">
        <?php $form = ActiveForm::begin(); ?>

        <?= $form->field($model, 'batch_id')->dropDownList($releasedBatches, ['prompt' => '— Select a released batch —'])
            ->hint('Only released batches appear here. Provenance fields below are inherited from the batch (AC-EC-05.3).') ?>

        <div class="row">
            <div class="col-md-8"><?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?></div>
            <div class="col-md-4"><?= $form->field($model, 'price')->textInput() ?></div>
        </div>

        <div class="row">
            <div class="col-md-8"><?= $form->field($model, 'wholesale_price')->textInput()
                ->label('Wholesale Price (€) — leave blank to use standard price for all customers') ?></div>
            <div class="col-md-4"><?= $form->field($model, 'stock_quantity')->textInput([
                'type' => 'number',
                'min'  => $model->isNewRecord ? 1 : 0,
                'max'  => $initialMax !== null ? $initialMax : null,
            ])->hint(
                '<span id="yield-hint">' . (
                    $initialMax !== null && $batch !== null
                        ? $initialMax . ' units remaining from batch ' . Html::encode($batch->lot_number)
                            . ' (after allocation to other products)'
                        : 'Packaged units available for sale.'
                ) . '</span>',
                ['encode' => false],
            ) ?></div>
        </div>

        <?= $form->field($model, 'description')->textarea(['rows' => 3]) ?>

        <?= $form->field($model, 'is_published')->checkbox()
            ->label('Publish in shop') ?>

        <?php if ($batch !== null): ?>
            <hr>
            <p class="fw-semibold mb-2 text-muted">Inherited from batch <?= Html::encode($batch->lot_number) ?> (read-only)</p>
            <div class="row g-2">
                <div class="col-md-4">
                    <label class="form-label">Honey Variety</label>
                    <input type="text" class="form-control" value="<?= Html::encode($batch->honey_variety) ?>" disabled>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Harvest Date</label>
                    <input type="text" class="form-control" value="<?= Html::encode((string) $batch->harvest_date) ?>" disabled>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Water Content</label>
                    <input type="text" class="form-control" value="<?= $batch->water_content !== null ? Html::encode($batch->water_content . ' %') : '—' ?>" disabled>
                </div>
                <div class="col-12">
                    <label class="form-label">Origin Statement</label>
                    <input type="text" class="form-control" value="<?= Html::encode((string) $batch->origin_statement) ?>" disabled>
                </div>
            </div>
        <?php endif ?>

        <div class="mt-4">
            <?= Html::submitButton('Save Product', ['class' => 'btn btn-warning']) ?>
            <?= Html::a('Cancel', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>

<?php
$yieldJson = Json::htmlEncode($batchYield);
$js = <<<JS
(function () {
    var yield = {$yieldJson};
    var \$batch = $('#product-batch_id');
    var \$stock = $('#product-stock_quantity');
    var \$hint  = $('#yield-hint');

    function syncYield() {
        var data = yield[\$batch.val()];
        if (data && data.maxUnits != null) {
            \$hint.text(data.maxUnits + ' units remaining from this batch (after allocation to other products)');
            \$stock.attr('max', data.maxUnits);
        } else {
            \$hint.text('Packaged units available for sale.');
            \$stock.removeAttr('max');
        }
    }
    \$batch.on('change', syncYield);
})();
JS;
$this->registerJs($js);

<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Batch $model */

use common\models\Batch;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;
use yii\helpers\Json;

// Container size dropdown: stored value is the display label (Change 2).
$containerKeys = array_keys(Batch::containerSizeOptions());
$containerList = array_combine($containerKeys, $containerKeys);
// Preserve any legacy free-text value that isn't one of the standard sizes.
if (!empty($model->container_size) && !isset($containerList[$model->container_size])) {
    $containerList = [$model->container_size => $model->container_size . ' (legacy)'] + $containerList;
}
$currentGrams = Batch::containerSizeGrams($model->container_size) ?? '';
$maxUnits     = $model->theoreticalMaxUnits(); // null until a container size is chosen
?>
<div class="card shadow-sm" style="max-width: 860px;">
    <div class="card-body">
        <?php $form = ActiveForm::begin(); ?>

        <div class="row">
            <div class="col-md-6"><?= $form->field($model, 'honey_variety')->dropDownList(
                Batch::honeyVarietyOptions(),
                ['prompt' => '— Select honey variety —'],
            )->hint('Determines the HonigV water-content limit (Heidehonig 23%, otherwise 20%).') ?></div>
            <div class="col-md-3"><?= $form->field($model, 'water_content')->textInput()
                ->hint('<span id="water-limit-hint">HonigV limit: 20% (23% for Heidehonig)</span>', ['encode' => false]) ?></div>
            <div class="col-md-3"><?= $form->field($model, 'hmf')->textInput()
                ->hint('HonigV limit: 40 mg/kg') ?></div>
        </div>

        <div class="row">
            <div class="col-md-3"><?= $form->field($model, 'conductivity')->textInput()
                ->hint('Waldhonig ≥ 0.8, Blütenhonig ≤ 0.8 mS/cm (informational).') ?></div>
            <div class="col-md-3"><?= $form->field($model, 'fill_date')->input('date') ?></div>
            <div class="col-md-3"><?= $form->field($model, 'container_size')->dropDownList(
                $containerList,
                ['prompt' => '— Select container size —'],
            ) ?>
                <?= Html::hiddenInput('container_size_grams', $currentGrams, ['id' => 'batch-container_size_grams']) ?>
            </div>
            <div class="col-md-3"><?= $form->field($model, 'packaged_unit_count')->textInput(['type' => 'number', 'min' => 0])
                ->hint('<span id="unit-max-hint">' . (
                    $maxUnits !== null
                        ? 'Theoretical max: ' . $maxUnits . ' units'
                        : 'Select a container size to see the maximum'
                ) . '</span>', ['encode' => false]) ?></div>
        </div>

        <div class="row">
            <div class="col-md-4"><?= $form->field($model, 'best_before_date')->input('date') ?></div>
            <div class="col-md-8"><?= $form->field($model, 'origin_statement')->textInput(['maxlength' => true])
                ->hint('Mandatory HonigV label field, e.g. "Honey from Germany (Bavaria, Landkreis Hof)".') ?></div>
        </div>

        <?= $form->field($model, 'haccp_confirmed')->checkbox()
            ->label('HACCP process confirmed (hygienic extraction & packaging)') ?>

        <div class="mt-3">
            <?= Html::submitButton('Save Batch Details', ['class' => 'btn btn-warning']) ?>
            <?= Html::a('Cancel', ['view', 'id' => $model->id], ['class' => 'btn btn-outline-secondary']) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>

<?php
$gramsMap = Json::htmlEncode(Batch::containerSizeOptions());
$harvestKg = (float) ($model->harvest_quantity_kg ?? 0);
$js = <<<JS
(function () {
    var grams     = {$gramsMap};
    var harvestKg = {$harvestKg};

    // Keep the hidden gram field in sync with the container size dropdown,
    // and recompute the theoretical-max unit hint.
    var \$container = $('#batch-container_size');
    var \$grams     = $('#batch-container_size_grams');
    var \$maxHint   = $('#unit-max-hint');
    \$container.on('change', function () {
        var g = grams[$(this).val()] || '';
        \$grams.val(g);
        if (g && harvestKg > 0) {
            \$maxHint.text('Theoretical max: ' + Math.floor((harvestKg * 1000) / g) + ' units');
        } else {
            \$maxHint.text('Select a container size to see the maximum');
        }
    });

    // Water-content hint reflects the selected honey variety.
    var \$variety = $('#batch-honey_variety');
    var \$hint    = $('#water-limit-hint');
    function syncWaterHint() {
        var v = \$variety.val();
        if (v && v.indexOf('Heidehonig') === 0) {
            \$hint.text('HonigV limit: 23% (Heidehonig)');
        } else {
            \$hint.text('HonigV limit: 20% (23% for Heidehonig)');
        }
    }
    \$variety.on('change', syncWaterHint);
    syncWaterHint();
})();
JS;
$this->registerJs($js);

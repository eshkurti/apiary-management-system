<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Inspection $model */

use common\models\ApiaryStand;
use common\models\Colony;
use yii\bootstrap5\ActiveForm;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

$stands = ArrayHelper::map(
    ApiaryStand::find()->orderBy(['stand_code' => SORT_ASC])->all(),
    'id',
    static fn (ApiaryStand $s): string => $s->stand_code . ' — ' . $s->name,
);

// The colony dropdown is populated dynamically from the selected stand (Fix 2).
// Pre-seed it with the currently selected colony, if any, so a re-rendered form
// (validation error or edit mode) keeps the choice before JS runs.
$colonyOptions = [];
if (!empty($model->colony_id)) {
    $current = Colony::findOne($model->colony_id);
    if ($current !== null) {
        $colonyOptions[$current->id] = $current->colony_code;
    }
}

$coloniesUrl   = Url::to(['inspection/colonies-for-stand']);
$currentColony = (int) ($model->colony_id ?? 0);
?>
<div class="card shadow-sm" style="max-width: 760px;">
    <div class="card-body">
        <?php $form = ActiveForm::begin(); ?>

        <div class="row">
            <div class="col-md-6"><?= $form->field($model, 'apiary_stand_id')->dropDownList($stands, ['prompt' => '— Select stand —'])
                ->hint('Select the stand first — the colony list updates to match.') ?></div>
            <div class="col-md-6"><?= $form->field($model, 'colony_id')->dropDownList($colonyOptions, [
                'prompt' => '— Select stand first —',
            ])->hint('Only colonies assigned to the selected stand are shown.') ?></div>
        </div>

        <div class="row">
            <div class="col-md-6"><?= $form->field($model, 'inspection_date')->input('date') ?></div>
            <div class="col-md-6"><?= $form->field($model, 'weather')->textInput(['maxlength' => true]) ?></div>
        </div>

        <div class="row">
            <div class="col-md-6"><?= $form->field($model, 'brood_pattern_score')->dropDownList(
                [1 => '1 — poor', 2 => '2', 3 => '3 — average', 4 => '4', 5 => '5 — excellent'],
                ['prompt' => '—'],
            ) ?></div>
            <div class="col-md-6"><?= $form->field($model, 'queen_sighted')->checkbox() ?></div>
        </div>

        <?= $form->field($model, 'disease_indicators')->textInput(['maxlength' => true])
            ->hint('Leave blank if none observed.') ?>
        <?= $form->field($model, 'notes')->textarea(['rows' => 3]) ?>

        <hr>
        <?= $form->field($model, 'feeding_applied')->checkbox() ?>

        <div id="feeding-quantity-wrap">
            <?= $form->field($model, 'feeding_quantity')->textInput(['maxlength' => true])
                ->hint('e.g. 1.5 kg fondant, 2L syrup') ?>
        </div>

        <div class="mt-3">
            <?= Html::submitButton('Save Inspection', ['class' => 'btn btn-warning']) ?>
            <?= Html::a('Cancel', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>

<?php
$coloniesUrlJs = Json::htmlEncode($coloniesUrl);
$currentColonyJs = Json::htmlEncode($currentColony);
$js = <<<JS
(function () {
    // ── Feeding quantity toggle ────────────────────────────────────────
    var \$check = $('#inspection-feeding_applied');
    var \$wrap  = $('#feeding-quantity-wrap');
    function syncFeeding() {
        if (\$check.is(':checked')) { \$wrap.show(); } else { \$wrap.hide(); }
    }
    \$check.on('change', syncFeeding);
    syncFeeding();

    // ── Dependent colony dropdown ──────────────────────────────────────
    var coloniesUrl   = {$coloniesUrlJs};
    var currentColony = {$currentColonyJs};
    var \$stand  = $('#inspection-apiary_stand_id');
    var \$colony = $('#inspection-colony_id');

    function loadColonies(standId, preselect) {
        if (!standId) {
            \$colony.empty().append('<option value="">— Select stand first —</option>');
            return;
        }
        \$.getJSON(coloniesUrl, { standId: standId }, function (rows) {
            \$colony.empty().append('<option value="">— Select colony —</option>');
            rows.forEach(function (c) {
                var label = c.colony_code;
                if (c.status !== 'active') { label += ' [' + c.status + ']'; }
                var \$opt = $('<option>').val(c.id).text(label);
                if (preselect && parseInt(c.id, 10) === parseInt(preselect, 10)) {
                    \$opt.prop('selected', true);
                }
                \$colony.append(\$opt);
            });
        });
    }

    \$stand.on('change', function () {
        loadColonies($(this).val(), null);
    });

    // On load: if a stand is already selected (edit / re-render), populate
    // immediately and keep the current colony selected.
    if (\$stand.val()) {
        loadColonies(\$stand.val(), currentColony);
    }
})();
JS;
$this->registerJs($js);
?>

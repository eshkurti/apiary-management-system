<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Batch $model */
/** @var int[] $selectedColonyIds */

use common\models\ApiaryStand;
use common\models\Batch;
use yii\bootstrap5\ActiveForm;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

$this->title = 'Record Harvest';
$this->params['breadcrumbs'][] = ['label' => 'Batches', 'url' => ['index']];
$this->params['breadcrumbs'][] = 'Harvest';

$stands = ArrayHelper::map(
    ApiaryStand::find()->orderBy(['stand_code' => SORT_ASC])->all(),
    'id',
    static fn (ApiaryStand $s): string => $s->stand_code . ' — ' . $s->name,
);

$coloniesUrl = Url::to(['batch/colonies-for-stand']);
?>
<h1 class="h3 mb-1"><?= $this->title ?></h1>
<p class="text-muted">
    Creates a batch (status <em>pending release</em>) permanently linked to its source colonies.
    Only colonies currently assigned to the selected stand can be chosen. Colonies within an active
    Wartezeit or carrying a disease flag are marked with a warning badge; they can still be ticked,
    but the release gate will block release until the issue clears (AC-PM-05.2).
</p>

<div class="card shadow-sm" style="max-width: 860px;">
    <div class="card-body">
        <?php $form = ActiveForm::begin(); ?>

        <div class="row">
            <div class="col-md-6"><?= $form->field($model, 'apiary_stand_id')->dropDownList($stands, ['prompt' => '— Select stand —'])
                ->hint('Select the stand first — the colony list updates to match.') ?></div>
            <div class="col-md-3"><?= $form->field($model, 'harvest_date')->input('date') ?></div>
            <div class="col-md-3"><?= $form->field($model, 'harvest_quantity_kg')->textInput()->label('Quantity (kg)') ?></div>
        </div>

        <?= $form->field($model, 'honey_variety')->dropDownList(
            Batch::honeyVarietyOptions(),
            ['prompt' => '— Select honey variety —'],
        ) ?>

        <label class="form-label fw-semibold mt-2">Source colonies</label>
        <?php if ($model->hasErrors('harvest_date')): ?>
            <div class="text-danger small mb-2"><?= Html::encode($model->getFirstError('harvest_date')) ?></div>
        <?php endif ?>
        <div id="harvest-colonies" class="row">
            <div class="col-12 text-muted small">Select a stand to list its colonies.</div>
        </div>

        <div class="mt-3">
            <?= Html::submitButton('Record Harvest', ['class' => 'btn btn-warning']) ?>
            <?= Html::a('Cancel', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>

<?php
$coloniesUrlJs = Json::htmlEncode($coloniesUrl);
$preselectedJs = Json::htmlEncode(array_map('intval', $selectedColonyIds));
$js = <<<JS
(function () {
    var coloniesUrl = {$coloniesUrlJs};
    var preselected = {$preselectedJs};

    var \$stand = $('#batch-apiary_stand_id');
    var \$wrap  = $('#harvest-colonies');

    function render(rows) {
        \$wrap.empty();
        if (!rows.length) {
            \$wrap.append('<div class="col-12 text-muted small">No active colonies are assigned to this stand.</div>');
            return;
        }
        rows.forEach(function (c) {
            var checked = preselected.indexOf(parseInt(c.id, 10)) !== -1 ? 'checked' : '';
            var badges = '';
            if (c.in_withdrawal) {
                badges += ' <span class="badge bg-warning text-dark">In withdrawal until ' + (c.withdrawal_until || '') + '</span>';
            }
            if (c.disease_flag) {
                badges += ' <span class="badge bg-danger">Disease flag active</span>';
            }
            var html =
                '<div class="col-md-4 mb-1"><div class="form-check">' +
                '<input class="form-check-input" type="checkbox" name="colony_ids[]" value="' + c.id + '" id="colony-' + c.id + '" ' + checked + '>' +
                '<label class="form-check-label" for="colony-' + c.id + '">' + c.colony_code + badges + '</label>' +
                '</div></div>';
            \$wrap.append(html);
        });
    }

    function load(standId) {
        if (!standId) {
            \$wrap.empty().append('<div class="col-12 text-muted small">Select a stand to list its colonies.</div>');
            return;
        }
        \$.getJSON(coloniesUrl, { standId: standId }, render);
    }

    \$stand.on('change', function () {
        preselected = []; // changing stand clears stale selections
        load($(this).val());
    });

    // On load (including re-render after a validation error) populate immediately
    // and keep any previously ticked colonies checked.
    if (\$stand.val()) {
        load(\$stand.val());
    }
})();
JS;
$this->registerJs($js);

<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Treatment $model */
/** @var int[] $selectedColonyIds */

use common\models\ApiaryStand;
use common\models\Treatment;
use common\models\TreatmentProduct;
use yii\bootstrap5\ActiveForm;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

$this->title = 'Record Bulk Treatment';
$this->params['breadcrumbs'][] = ['label' => 'Treatments', 'url' => ['index']];
$this->params['breadcrumbs'][] = 'Bulk';

$stands = ArrayHelper::map(
    ApiaryStand::find()->orderBy(['stand_code' => SORT_ASC])->all(),
    'id',
    static fn (ApiaryStand $s): string => $s->stand_code . ' — ' . $s->name,
);

$productsByType = [
    Treatment::TYPE_VARROA        => TreatmentProduct::mapForType(Treatment::TYPE_VARROA),
    Treatment::TYPE_TRACHEENMILBE => TreatmentProduct::mapForType(Treatment::TYPE_TRACHEENMILBE),
];

$productDataUrl = Url::to(['treatment/product-data']);
$coloniesUrl    = Url::to(['treatment/colonies-for-stand']);
?>
<h1 class="h3 mb-1"><?= $this->title ?> <small class="text-muted fs-6">(one Bestandsbuch entry per colony)</small></h1>
<p class="text-muted">
    Applies the same treatment to every selected colony at a stand in one step. One individual
    treatment record is created per colony — all identical except the colony — so each colony's
    Wartezeit and annual compliance flags update correctly.
</p>

<div class="card shadow-sm" style="max-width: 860px;">
    <div class="card-body">
        <?php $form = ActiveForm::begin(['id' => 'bulk-treatment-form']); ?>

        <?= $form->field($model, 'apiary_stand_id')->dropDownList($stands, ['prompt' => '— Select stand —'])
            ->hint('Select the stand first — its active colonies appear below, all pre-selected.') ?>

        <label class="form-label fw-semibold">Colonies to treat</label>
        <?php if ($model->hasErrors('apiary_stand_id')): ?>
            <div class="text-danger small mb-2"><?= Html::encode($model->getFirstError('apiary_stand_id')) ?></div>
        <?php endif ?>
        <div id="bulk-colonies" class="row mb-3">
            <div class="col-12 text-muted small">Select a stand to list its colonies.</div>
        </div>

        <hr>

        <div class="row">
            <div class="col-md-4"><?= $form->field($model, 'treatment_type')->dropDownList(Treatment::typeLabels()) ?></div>
            <div class="col-md-8" id="product-picker-wrap">
                <label class="form-label" for="product-picker">Approved Product</label>
                <select id="product-picker" class="form-select">
                    <option value="">— Select approved product —</option>
                </select>
                <div class="form-text">Selecting a product autofills the fields below. All values remain editable.</div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6"><?= $form->field($model, 'product_name')->textInput(['maxlength' => true])
                ->hint('Trade name of the medicinal product.') ?></div>
            <div class="col-md-6"><?= $form->field($model, 'pharmaceutical_batch_number')->textInput(['maxlength' => true])
                ->hint('Chargennummer — found on the product packaging label.') ?></div>
        </div>

        <div class="row">
            <div class="col-md-4"><?= $form->field($model, 'application_date')->input('date') ?></div>
            <div class="col-md-4"><?= $form->field($model, 'withdrawal_days')->textInput()
                ->hint('Statutory Wartezeit in days. Required for Varroa / Tracheenmilbe.') ?></div>
            <div class="col-md-4"><?= $form->field($model, 'treatment_duration_days')->textInput() ?></div>
        </div>

        <?= $form->field($model, 'quantity_per_colony')->textInput(['maxlength' => true])
            ->hint('e.g. 50 ml, 2 strips — applied to each colony.') ?>

        <div class="row">
            <div class="col-md-6"><?= $form->field($model, 'supplier_name')->textInput(['maxlength' => true]) ?></div>
            <div class="col-md-6"><?= $form->field($model, 'supplier_address')->textInput(['maxlength' => true]) ?></div>
        </div>

        <div class="row">
            <div class="col-md-6"><?= $form->field($model, 'receipt_number')->textInput(['maxlength' => true])
                ->hint('Belegnummer — the receipt or invoice number from the pharmacy or supplier') ?></div>
            <div class="col-md-6"><?= $form->field($model, 'veterinarian')->textInput(['maxlength' => true])
                ->hint('Only required if a veterinarian prescribed or supervised the treatment (ggf. Name und Anschrift des Tierarztes)') ?></div>
        </div>

        <?= $form->field($model, 'operator_name')->textInput(['maxlength' => true])
            ->hint('Defaults to your username — editable.') ?>
        <?= $form->field($model, 'notes')->textarea(['rows' => 2]) ?>

        <div class="mt-2">
            <?= Html::submitButton('Record Treatment for Selected Colonies', ['class' => 'btn btn-warning']) ?>
            <?= Html::a('Cancel', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>

<?php
$productsJson  = Json::htmlEncode($productsByType);
$dataUrlJs     = Json::htmlEncode($productDataUrl);
$coloniesUrlJs = Json::htmlEncode($coloniesUrl);
$preselectedJs = Json::htmlEncode(array_map('intval', $selectedColonyIds));
$js = <<<JS
(function () {
    var products    = {$productsJson};
    var dataUrl     = {$dataUrlJs};
    var coloniesUrl = {$coloniesUrlJs};
    var preselected = {$preselectedJs};
    var hadPost     = preselected.length > 0;

    var \$type   = $('#treatment-treatment_type');
    var \$wrap   = $('#product-picker-wrap');
    var \$picker = $('#product-picker');
    var \$stand  = $('#treatment-apiary_stand_id');
    var \$cols   = $('#bulk-colonies');

    function renderColonies(rows, preCheckAll) {
        \$cols.empty();
        if (!rows.length) {
            \$cols.append('<div class="col-12 text-muted small">No active colonies are assigned to this stand.</div>');
            return;
        }
        rows.forEach(function (c) {
            var checked;
            if (preCheckAll) {
                checked = 'checked';
            } else {
                checked = preselected.indexOf(parseInt(c.id, 10)) !== -1 ? 'checked' : '';
            }
            var badge = c.in_withdrawal ? ' <span class="badge bg-warning text-dark">in withdrawal</span>' : '';
            var html =
                '<div class="col-md-4 mb-1"><div class="form-check">' +
                '<input class="form-check-input" type="checkbox" name="colony_ids[]" value="' + c.id + '" id="bcol-' + c.id + '" ' + checked + '>' +
                '<label class="form-check-label" for="bcol-' + c.id + '">' + c.colony_code + badge + '</label>' +
                '</div></div>';
            \$cols.append(html);
        });
    }

    function loadColonies(standId, preCheckAll) {
        if (!standId) {
            \$cols.empty().append('<div class="col-12 text-muted small">Select a stand to list its colonies.</div>');
            return;
        }
        \$.getJSON(coloniesUrl, { standId: standId }, function (rows) {
            renderColonies(rows, preCheckAll);
        });
    }

    \$stand.on('change', function () {
        preselected = [];
        loadColonies($(this).val(), true); // new stand → all colonies pre-checked
    });

    // On load: if a stand is selected (re-render after validation error) keep the
    // user's previous ticks; otherwise nothing to show yet.
    if (\$stand.val()) {
        loadColonies(\$stand.val(), !hadPost);
    }

    // ── Product picker autofill (identical to the single treatment form) ──
    function rebuildPicker(type) {
        \$picker.empty().append('<option value="">— Select approved product —</option>');
        var list = products[type] || {};
        Object.keys(list).forEach(function (id) {
            \$picker.append($('<option>').val(id).text(list[id]));
        });
    }

    function syncVisibility() {
        var type = \$type.val();
        if (type === 'varroa' || type === 'tracheenmilbe') {
            rebuildPicker(type);
            \$wrap.show();
        } else {
            \$wrap.hide();
            \$picker.val('');
        }
    }

    \$type.on('change', syncVisibility);

    \$picker.on('change', function () {
        var id = $(this).val();
        if (!id) { return; }
        $.getJSON(dataUrl, { id: id }, function (data) {
            if (data.product_name)            { $('#treatment-product_name').val(data.product_name); }
            if (data.withdrawal_days != null) { $('#treatment-withdrawal_days').val(data.withdrawal_days); }
            if (data.duration_days != null)   { $('#treatment-treatment_duration_days').val(data.duration_days); }
            if (data.quantity != null)        { $('#treatment-quantity_per_colony').val(data.quantity); }
            if (data.supplier_name != null)   { $('#treatment-supplier_name').val(data.supplier_name); }
            if (data.supplier_address != null){ $('#treatment-supplier_address').val(data.supplier_address); }
        });
    });

    syncVisibility();
})();
JS;
$this->registerJs($js);

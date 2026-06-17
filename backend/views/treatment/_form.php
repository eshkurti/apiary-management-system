<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Treatment $model */

use common\models\ApiaryStand;
use common\models\Colony;
use common\models\Treatment;
use common\models\TreatmentProduct;
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

// The colony dropdown is populated dynamically from the selected stand
// (Change 1). Pre-seed it with the currently selected colony, if any, so a
// re-rendered form (validation error) keeps the choice before JS runs.
$colonyOptions = [];
if (!empty($model->colony_id)) {
    $current = Colony::findOne($model->colony_id);
    if ($current !== null) {
        $colonyOptions[$current->id] = $current->colony_code;
    }
}

// Products grouped by treatment type, used to build the conditional picker.
$productsByType = [
    Treatment::TYPE_VARROA        => TreatmentProduct::mapForType(Treatment::TYPE_VARROA),
    Treatment::TYPE_TRACHEENMILBE => TreatmentProduct::mapForType(Treatment::TYPE_TRACHEENMILBE),
];

$productDataUrl = Url::to(['treatment/product-data']);
$coloniesUrl    = Url::to(['treatment/colonies-for-stand']);
?>
<div class="card shadow-sm" style="max-width: 860px;">
    <div class="card-body">
        <?php $form = ActiveForm::begin(['id' => 'treatment-form']); ?>

        <div class="row">
            <div class="col-md-6"><?= $form->field($model, 'apiary_stand_id')->dropDownList($stands, ['prompt' => '— Select stand —'])
                ->hint('Select the stand first — the colony list updates to match.') ?></div>
            <div class="col-md-6"><?= $form->field($model, 'colony_id')->dropDownList($colonyOptions, [
                'prompt' => '— Select stand first —',
            ])->hint('Only colonies assigned to the selected stand are shown.') ?></div>
        </div>

        <hr>

        <!-- Step 1: treatment type -->
        <div class="row">
            <div class="col-md-4"><?= $form->field($model, 'treatment_type')->dropDownList(Treatment::typeLabels()) ?></div>

            <!-- Step 2a: approved-product picker (Varroa / Tracheenmilbe) -->
            <div class="col-md-8" id="product-picker-wrap">
                <label class="form-label" for="product-picker">Approved Product</label>
                <select id="product-picker" class="form-select">
                    <option value="">— Select approved product —</option>
                </select>
                <div class="form-text">Selecting a product autofills the fields below. All values remain editable.</div>
            </div>
        </div>

        <!-- Step 2b: product name (free text for "Other", auto-filled for the dropdown) -->
        <div class="row">
            <div class="col-md-6"><?= $form->field($model, 'product_name')->textInput(['maxlength' => true])
                ->hint('Trade name of the medicinal product.') ?></div>
            <div class="col-md-6"><?= $form->field($model, 'pharmaceutical_batch_number')->textInput(['maxlength' => true])
                ->hint('Chargennummer — found on the product packaging label.') ?></div>
        </div>

        <div class="row">
            <div class="col-md-4"><?= $form->field($model, 'application_date')->input('date') ?></div>
            <div class="col-md-4"><?= $form->field($model, 'withdrawal_days')->textInput()
                ->hint('Statutory Wartezeit in days. Required for Varroa / Tracheenmilbe (AC-PM-04.6).') ?></div>
            <div class="col-md-4"><?= $form->field($model, 'treatment_duration_days')->textInput() ?></div>
        </div>

        <div class="row">
            <div class="col-md-12"><?= $form->field($model, 'quantity_per_colony')->textInput(['maxlength' => true])
                ->hint('e.g. 50 ml, 2 strips') ?></div>
        </div>

        <div class="row">
            <div class="col-md-6"><?= $form->field($model, 'supplier_name')->textInput(['maxlength' => true]) ?></div>
            <div class="col-md-6"><?= $form->field($model, 'supplier_address')->textInput(['maxlength' => true]) ?></div>
        </div>

        <?= $form->field($model, 'operator_name')->textInput(['maxlength' => true])
            ->hint('Defaults to your username — editable.') ?>
        <?= $form->field($model, 'notes')->textarea(['rows' => 2]) ?>

        <p class="text-muted small">
            The Wartezeit expiry date is calculated automatically on save
            (application date + withdrawal period, AC-PM-04.3).
        </p>

        <div class="mt-2">
            <?= Html::submitButton('Record Treatment', ['class' => 'btn btn-warning']) ?>
            <?= Html::a('Cancel', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>

<?php
$productsJson  = Json::htmlEncode($productsByType);
$dataUrlJs     = Json::htmlEncode($productDataUrl);
$coloniesUrlJs = Json::htmlEncode($coloniesUrl);
$currentColony = Json::htmlEncode((int) ($model->colony_id ?? 0));
$js = <<<JS
(function () {
    var products = {$productsJson};
    var dataUrl  = {$dataUrlJs};
    var coloniesUrl = {$coloniesUrlJs};
    var currentColony = {$currentColony};

    var \$type    = $('#treatment-treatment_type');
    var \$wrap    = $('#product-picker-wrap');
    var \$picker  = $('#product-picker');

    // ── Dependent colony dropdown ──────────────────────────────────────
    var \$stand  = $('#treatment-apiary_stand_id');
    var \$colony = $('#treatment-colony_id');

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
                if (c.in_withdrawal) { label += ' — in withdrawal'; }
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

    // On load: if a stand is already selected, populate immediately
    // and keep the current colony selected (edit / re-render).
    if (\$stand.val()) {
        loadColonies(\$stand.val(), currentColony);
    }

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
            if (data.product_name)     { $('#treatment-product_name').val(data.product_name); }
            if (data.withdrawal_days != null)  { $('#treatment-withdrawal_days').val(data.withdrawal_days); }
            if (data.duration_days != null)    { $('#treatment-treatment_duration_days').val(data.duration_days); }
            if (data.quantity != null)         { $('#treatment-quantity_per_colony').val(data.quantity); }
            if (data.supplier_name != null)    { $('#treatment-supplier_name').val(data.supplier_name); }
            if (data.supplier_address != null) { $('#treatment-supplier_address').val(data.supplier_address); }
        });
    });

    syncVisibility();
})();
JS;
$this->registerJs($js);

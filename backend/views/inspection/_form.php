<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Inspection $model */

use common\models\ApiaryStand;
use common\models\Colony;
use yii\bootstrap5\ActiveForm;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

$colonies = ArrayHelper::map(
    Colony::find()->with('apiaryStand')->orderBy(['colony_code' => SORT_ASC])->all(),
    'id',
    static fn (Colony $c): string => $c->colony_code . ' (' . ($c->apiaryStand->stand_code ?? '—') . ')',
);

$stands = ArrayHelper::map(
    ApiaryStand::find()->orderBy(['stand_code' => SORT_ASC])->all(),
    'id',
    static fn (ApiaryStand $s): string => $s->stand_code . ' — ' . $s->name,
);
?>
<div class="card shadow-sm" style="max-width: 760px;">
    <div class="card-body">
        <?php $form = ActiveForm::begin(); ?>

        <div class="row">
            <div class="col-md-6"><?= $form->field($model, 'colony_id')->dropDownList($colonies, ['prompt' => '— Select colony —']) ?></div>
            <div class="col-md-6"><?= $form->field($model, 'apiary_stand_id')->dropDownList($stands, ['prompt' => '— Select stand —'])
                ->hint('The stand the colony was inspected at.') ?></div>
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
$js = <<<'JS'
(function () {
    var $check = $('#inspection-feeding_applied');
    var $wrap  = $('#feeding-quantity-wrap');
    function sync() {
        if ($check.is(':checked')) { $wrap.show(); } else { $wrap.hide(); }
    }
    $check.on('change', sync);
    sync();
})();
JS;
$this->registerJs($js);
?>

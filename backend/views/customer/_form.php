<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Customer $model */

use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;
?>
<div class="card shadow-sm" style="max-width: 820px;">
    <div class="card-body">
        <?php $form = ActiveForm::begin(); ?>

        <div class="row">
            <div class="col-md-6"><?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?></div>
            <div class="col-md-6"><?= $form->field($model, 'email')->textInput(['maxlength' => true]) ?></div>
        </div>

        <div class="row">
            <div class="col-md-6"><?= $form->field($model, 'phone')->textInput(['maxlength' => true]) ?></div>
            <div class="col-md-6"><?= $form->field($model, 'company')->textInput(['maxlength' => true])
                ->hint('For business / wholesale buyers (AC-EC-07.1).') ?></div>
        </div>

        <?= $form->field($model, 'address')->textInput(['maxlength' => true]) ?>

        <div class="row">
            <div class="col-md-3"><?= $form->field($model, 'postcode')->textInput(['maxlength' => true]) ?></div>
            <div class="col-md-5"><?= $form->field($model, 'city')->textInput(['maxlength' => true]) ?></div>
            <div class="col-md-4"><?= $form->field($model, 'country')->textInput(['maxlength' => true]) ?></div>
        </div>

        <hr>

        <div class="row align-items-center">
            <div class="col-md-4"><?= $form->field($model, 'is_wholesale')->checkbox() ?></div>
            <div class="col-md-4" id="moq-wrap" style="<?= $model->is_wholesale ? '' : 'display:none;' ?>">
                <?= $form->field($model, 'min_order_quantity')->textInput()
                    ->hint('Required for wholesale; enforced at checkout (AC-EC-07.4).') ?>
            </div>
            <div class="col-md-4"><?= $form->field($model, 'is_active')->checkbox() ?></div>
        </div>

        <div class="mt-3">
            <?= Html::submitButton('Save Customer', ['class' => 'btn btn-warning']) ?>
            <?= Html::a('Cancel', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>
<?php
$this->registerJs(<<<JS
(function () {
    var \$cb  = $('#customer-is_wholesale');
    var \$moq = $('#moq-wrap');
    function sync() {
        if (\$cb.is(':checked')) {
            \$moq.show();
        } else {
            \$moq.hide();
            \$moq.find('input').val('');
        }
    }
    \$cb.on('change', sync);
})();
JS);
?>

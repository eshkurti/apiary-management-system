<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\ApiaryStand $model */
/** @var yii\bootstrap5\ActiveForm $form */

use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;
?>
<div class="card shadow-sm" style="max-width: 760px;">
    <div class="card-body">
        <?php $form = ActiveForm::begin(); ?>

        <div class="row">
            <div class="col-md-4"><?= $form->field($model, 'stand_code')->textInput(['maxlength' => true])
                ->hint('Unique stand identifier, e.g. LIN-S-001.') ?></div>
            <div class="col-md-8"><?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?></div>
        </div>

        <div class="row">
            <div class="col-md-6"><?= $form->field($model, 'latitude')->textInput()
                ->hint('Decimal latitude, e.g. 50.3214') ?></div>
            <div class="col-md-6"><?= $form->field($model, 'longitude')->textInput()
                ->hint('Decimal longitude, e.g. 11.9112') ?></div>
        </div>

        <div class="row">
            <div class="col-md-6"><?= $form->field($model, 'landkreis')->textInput(['maxlength' => true]) ?></div>
            <div class="col-md-6"><?= $form->field($model, 'authority_reg_number')->textInput(['maxlength' => true])
                ->hint('Veterinäramt registration number (§ 1a BienSeuchV).') ?></div>
        </div>

        <?= $form->field($model, 'is_active')->checkbox() ?>

        <div class="mt-3">
            <?= Html::submitButton('Save', ['class' => 'btn btn-warning']) ?>
            <?= Html::a('Cancel', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>

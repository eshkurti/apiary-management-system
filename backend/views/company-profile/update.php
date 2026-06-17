<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\CompanyProfile $model */

use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;

$this->title = 'Company Profile';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Company Profile</h1>
</div>

<p class="text-muted">
    The legal keeper identity (<em>Tierhalter</em>) used in the header of every
    <em>Bestandsbuch</em> compliance export (EU Reg. 2019/6 Art. 108(2)(f)).
</p>

<div class="card shadow-sm" style="max-width: 720px;">
    <div class="card-body">
        <?php $form = ActiveForm::begin(); ?>

        <?= $form->field($model, 'company_name')->textInput(['maxlength' => true]) ?>
        <?= $form->field($model, 'keeper_name')->textInput(['maxlength' => true])
            ->hint('The legal keeper (Tierhalter) responsible for the operation.') ?>
        <?= $form->field($model, 'address')->textInput(['maxlength' => true]) ?>

        <div class="row">
            <div class="col-md-4"><?= $form->field($model, 'postcode')->textInput(['maxlength' => true]) ?></div>
            <div class="col-md-8"><?= $form->field($model, 'city')->textInput(['maxlength' => true]) ?></div>
        </div>

        <div class="row">
            <div class="col-md-6"><?= $form->field($model, 'phone')->textInput(['maxlength' => true]) ?></div>
            <div class="col-md-6"><?= $form->field($model, 'email')->textInput(['maxlength' => true]) ?></div>
        </div>

        <div class="mt-3">
            <?= Html::submitButton('Save Profile', ['class' => 'btn btn-warning']) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>

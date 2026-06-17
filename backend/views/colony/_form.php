<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Colony $model */

use common\models\ApiaryStand;
use yii\bootstrap5\ActiveForm;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

$stands = ArrayHelper::map(
    ApiaryStand::find()->orderBy(['stand_code' => SORT_ASC])->all(),
    'id',
    static fn (ApiaryStand $s): string => $s->stand_code . ' — ' . $s->name,
);

$statuses = [
    'active'   => 'Active',
    'inactive' => 'Inactive',
    'lost'     => 'Lost',
];
?>
<div class="card shadow-sm" style="max-width: 720px;">
    <div class="card-body">
        <?php $form = ActiveForm::begin(); ?>

        <?= $form->field($model, 'colony_code')->textInput(['maxlength' => true])
            ->hint('Unique colony identifier, e.g. LIN-C-001.') ?>
        <?= $form->field($model, 'apiary_stand_id')->dropDownList($stands, ['prompt' => '— Select a stand —']) ?>

        <div class="row">
            <div class="col-md-6"><?= $form->field($model, 'queen_year')->textInput() ?></div>
            <div class="col-md-6"><?= $form->field($model, 'status')->dropDownList($statuses) ?></div>
        </div>

        <div class="mt-3">
            <?= Html::submitButton('Save', ['class' => 'btn btn-warning']) ?>
            <?= Html::a('Cancel', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>

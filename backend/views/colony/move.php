<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\ColonyMovement $movement */
/** @var common\models\Colony $colony */

use common\models\ApiaryStand;
use yii\bootstrap5\ActiveForm;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

$this->title = 'Move Colony ' . $colony->colony_code;
$this->params['breadcrumbs'][] = ['label' => 'Colonies', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $colony->colony_code, 'url' => ['view', 'id' => $colony->id]];
$this->params['breadcrumbs'][] = 'Move';

$stands = ArrayHelper::map(
    ApiaryStand::find()->orderBy(['stand_code' => SORT_ASC])->all(),
    'id',
    static fn (ApiaryStand $s): string => $s->stand_code . ' — ' . $s->name,
);
?>
<h1 class="h3 mb-3"><?= Html::encode($this->title) ?></h1>

<p class="text-muted">
    Current stand:
    <strong><?= Html::encode($colony->apiaryStand->stand_code ?? '—') ?></strong>.
    Historical inspection and treatment records keep the stand they were recorded at (AC-PM-02.4).
</p>

<div class="card shadow-sm" style="max-width: 640px;">
    <div class="card-body">
        <?php $form = ActiveForm::begin(); ?>

        <?= $form->field($movement, 'to_stand_id')->dropDownList($stands, ['prompt' => '— Select destination —']) ?>
        <?= $form->field($movement, 'movement_date')->input('date') ?>
        <?= $form->field($movement, 'notes')->textarea(['rows' => 2]) ?>

        <div class="mt-3">
            <?= Html::submitButton('Record Movement', ['class' => 'btn btn-warning']) ?>
            <?= Html::a('Cancel', ['view', 'id' => $colony->id], ['class' => 'btn btn-outline-secondary']) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>

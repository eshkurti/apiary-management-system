<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Batch $model */
/** @var int[] $selectedColonyIds */

use common\models\ApiaryStand;
use common\models\Batch;
use common\models\Colony;
use yii\bootstrap5\ActiveForm;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

$this->title = 'Record Harvest';
$this->params['breadcrumbs'][] = ['label' => 'Batches', 'url' => ['index']];
$this->params['breadcrumbs'][] = 'Harvest';

$stands = ArrayHelper::map(
    ApiaryStand::find()->orderBy(['stand_code' => SORT_ASC])->all(),
    'id',
    static fn (ApiaryStand $s): string => $s->stand_code . ' — ' . $s->name,
);

$colonies = Colony::find()->with('apiaryStand')->where(['status' => 'active'])->orderBy(['colony_code' => SORT_ASC])->all();
?>
<h1 class="h3 mb-1"><?= $this->title ?></h1>
<p class="text-muted">
    Creates a batch (status <em>pending release</em>) permanently linked to its source colonies.
    Colonies within an active Wartezeit or carrying a disease flag are marked with a warning badge.
    They can still be selected, but the release gate will block release until the issue clears (AC-PM-05.2).
</p>

<div class="card shadow-sm" style="max-width: 860px;">
    <div class="card-body">
        <?php $form = ActiveForm::begin(); ?>

        <div class="row">
            <div class="col-md-6"><?= $form->field($model, 'apiary_stand_id')->dropDownList($stands, ['prompt' => '— Select stand —'])
                ->hint('The stand the source colonies were located at on the harvest date.') ?></div>
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
        <div class="row">
            <?php foreach ($colonies as $c): ?>
                <?php
                // Colonies remain selectable even when blocked; the warning
                // badges make the reason visible. The release gate enforces
                // eligibility definitively (AC-PM-05.2).
                $withdrawalExpiry = $c->getLatestWartezeitExpiry();
                $inWithdrawal     = !$c->isWithdrawalCleared(date('Y-m-d'));
                ?>
                <div class="col-md-4 mb-1">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="colony_ids[]"
                               value="<?= $c->id ?>" id="colony-<?= $c->id ?>"
                               <?= in_array($c->id, $selectedColonyIds, true) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="colony-<?= $c->id ?>">
                            <?= Html::encode($c->colony_code) ?>
                            <span class="text-muted">(<?= Html::encode($c->apiaryStand->stand_code ?? '—') ?>)</span>
                            <?php if ($inWithdrawal): ?>
                                <span class="badge bg-warning text-dark">In withdrawal until <?= Html::encode((string) $withdrawalExpiry) ?></span>
                            <?php endif ?>
                            <?php if ($c->disease_flag): ?>
                                <span class="badge bg-danger">Disease flag active</span>
                            <?php endif ?>
                        </label>
                    </div>
                </div>
            <?php endforeach ?>
        </div>

        <div class="mt-3">
            <?= Html::submitButton('Record Harvest', ['class' => 'btn btn-warning']) ?>
            <?= Html::a('Cancel', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>

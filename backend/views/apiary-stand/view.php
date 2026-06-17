<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\ApiaryStand $model */

use yii\bootstrap5\Html;
use yii\widgets\DetailView;

$this->title = $model->stand_code . ' — ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Apiary Stands', 'url' => ['index']];
$this->params['breadcrumbs'][] = $model->stand_code;
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0"><?= Html::encode($this->title) ?></h1>
    <div>
        <?= Html::a('Edit', ['update', 'id' => $model->id], ['class' => 'btn btn-outline-secondary']) ?>
        <?= Html::a('Delete', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-outline-danger',
            'data' => [
                'confirm' => 'Delete this apiary stand?',
                'method' => 'post',
            ],
        ]) ?>
    </div>
</div>

<div class="card shadow-sm" style="max-width: 760px;">
    <div class="card-body">
        <?= DetailView::widget([
            'model' => $model,
            'options' => ['class' => 'table table-striped mb-0'],
            'attributes' => [
                'stand_code',
                'name',
                'latitude',
                'longitude',
                'landkreis',
                'authority_reg_number',
                [
                    'attribute' => 'is_active',
                    'format' => 'boolean',
                ],
                [
                    'label' => 'Registered',
                    'value' => Yii::$app->formatter->asDate($model->created_at),
                ],
            ],
        ]) ?>
    </div>
</div>

<p class="mt-3">
    <?= Html::a('View colonies at this stand', ['/colony/index'], ['class' => 'btn btn-sm btn-outline-secondary']) ?>
</p>

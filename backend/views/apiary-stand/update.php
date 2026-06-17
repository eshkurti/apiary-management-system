<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\ApiaryStand $model */

use yii\helpers\Html;

$this->title = 'Edit Stand: ' . $model->stand_code;
$this->params['breadcrumbs'][] = ['label' => 'Apiary Stands', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->stand_code, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Edit';
?>
<h1 class="h3 mb-3"><?= Html::encode($this->title) ?></h1>

<?= $this->render('_form', ['model' => $model]) ?>

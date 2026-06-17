<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Colony $model */

use yii\helpers\Html;

$this->title = 'Edit Colony: ' . $model->colony_code;
$this->params['breadcrumbs'][] = ['label' => 'Colonies', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->colony_code, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Edit';
?>
<h1 class="h3 mb-3"><?= Html::encode($this->title) ?></h1>

<?= $this->render('_form', ['model' => $model]) ?>

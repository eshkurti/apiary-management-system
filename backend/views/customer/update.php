<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Customer $model */

use yii\helpers\Html;

$this->title = 'Edit Customer: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Customers', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Edit';
?>
<h1 class="h3 mb-3"><?= Html::encode($this->title) ?></h1>

<?= $this->render('_form', ['model' => $model]) ?>

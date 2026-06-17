<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Customer $model */

$this->title = 'New Customer';
$this->params['breadcrumbs'][] = ['label' => 'Customers', 'url' => ['index']];
$this->params['breadcrumbs'][] = 'New';
?>
<h1 class="h3 mb-3"><?= $this->title ?></h1>

<?= $this->render('_form', ['model' => $model]) ?>

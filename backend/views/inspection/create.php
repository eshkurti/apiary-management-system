<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Inspection $model */

$this->title = 'Log Inspection';
$this->params['breadcrumbs'][] = ['label' => 'Inspections', 'url' => ['index']];
$this->params['breadcrumbs'][] = 'Log';
?>
<h1 class="h3 mb-3"><?= $this->title ?></h1>

<?= $this->render('_form', ['model' => $model]) ?>

<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Colony $model */

$this->title = 'Register Colony';
$this->params['breadcrumbs'][] = ['label' => 'Colonies', 'url' => ['index']];
$this->params['breadcrumbs'][] = 'Register';
?>
<h1 class="h3 mb-3"><?= $this->title ?></h1>

<?= $this->render('_form', ['model' => $model]) ?>

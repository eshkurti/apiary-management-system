<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Product $model */

$this->title = 'New Product';
$this->params['breadcrumbs'][] = ['label' => 'Products', 'url' => ['index']];
$this->params['breadcrumbs'][] = 'New';
?>
<h1 class="h3 mb-3"><?= $this->title ?></h1>

<?= $this->render('_form', ['model' => $model]) ?>

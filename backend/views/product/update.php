<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Product $model */

use yii\helpers\Html;

$this->title = 'Edit Product: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Products', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Edit';
?>
<h1 class="h3 mb-3"><?= Html::encode($this->title) ?></h1>

<?php if ((int) $model->stock_quantity === 0 && $model->is_published): ?>
    <div class="alert alert-info">
        This product is sold out. It remains published so customers can access its lot traceability page.
        Unpublish only if you need to withdraw this lot from public view.
    </div>
<?php endif ?>

<?= $this->render('_form', ['model' => $model]) ?>

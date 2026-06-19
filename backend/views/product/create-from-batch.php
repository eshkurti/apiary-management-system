<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Product $model */
/** @var common\models\Batch $batch */

$this->title = 'New Product from Batch ' . $batch->lot_number;
$this->params['breadcrumbs'][] = ['label' => 'Products', 'url' => ['index']];
$this->params['breadcrumbs'][] = 'New from batch';
?>
<h1 class="h3 mb-1"><?= $this->title ?></h1>
<p class="text-muted">
    Provenance is inherited from the batch. Just set the price and how many units to list.
</p>

<?= $this->render('_batch-product-form', ['model' => $model, 'batch' => $batch]) ?>

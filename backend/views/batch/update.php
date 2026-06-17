<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Batch $model */

use yii\helpers\Html;

$this->title = 'Complete Batch Details: ' . $model->lot_number;
$this->params['breadcrumbs'][] = ['label' => 'Batches', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->lot_number, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Complete details';
?>
<h1 class="h3 mb-1"><?= Html::encode($this->title) ?></h1>
<p class="text-muted">Lot number (Losnummer) is fixed at creation and cannot be changed (AC-PM-06.2).</p>

<?= $this->render('_form', ['model' => $model]) ?>

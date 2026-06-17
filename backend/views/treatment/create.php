<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Treatment $model */

$this->title = 'Record Treatment';
$this->params['breadcrumbs'][] = ['label' => 'Treatments', 'url' => ['index']];
$this->params['breadcrumbs'][] = 'Record';
?>
<h1 class="h3 mb-1"><?= $this->title ?> <small class="text-muted fs-6">(Bestandsbuch entry)</small></h1>

<?= $this->render('_form', ['model' => $model]) ?>

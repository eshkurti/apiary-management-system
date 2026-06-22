<?php

declare(strict_types=1);

/** @var yii\web\View $this */

use yii\helpers\Html;
use yii\helpers\Url;

?>
<footer id="footer" class="mt-auto py-3 bg-body-tertiary">
    <div class="container">
        <div class="row text-body-secondary">
            <div class="col-md-6 text-center text-md-start">&copy; <?= Html::encode(Yii::$app->name) ?> <?= date('Y') ?></div>
            <div class="col-md-6 text-center text-md-end">
                <?= Html::a('Impressum', Url::to(['site/impressum']), ['class' => 'text-body-secondary text-decoration-none me-3']) ?>
                <?= Html::a('Über uns', Url::to(['site/about']), ['class' => 'text-body-secondary text-decoration-none me-3']) ?>
            </div>
        </div>
    </div>
</footer>

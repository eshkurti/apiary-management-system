<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var \common\models\LoginForm $model */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$this->title = 'Log in';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="row justify-content-center py-4">
    <div class="col-md-6 col-lg-5">
        <div class="card">
            <div class="card-body p-4 p-lg-5">
                <div class="text-center mb-4">
                    <div style="font-size: 2.4rem;">🍯</div>
                    <h1 class="h4 fw-bold mb-1"><?= Html::encode($this->title) ?></h1>
                    <p class="text-muted small mb-0">Log in to your Lindenhof shop account.</p>
                </div>

                <?php $form = ActiveForm::begin(['id' => 'login-form']); ?>

                <?= $form->field($model, 'username')->textInput(['autofocus' => true, 'placeholder' => 'Your username'])
                    ->label('Username') ?>

                <?= $form->field($model, 'password')->passwordInput(['placeholder' => 'Your password'])
                    ->label('Password') ?>

                <?= $form->field($model, 'rememberMe')->checkbox() ?>

                <div class="d-grid mt-3">
                    <?= Html::submitButton('Log in', ['class' => 'btn btn-honey btn-lg', 'name' => 'login-button']) ?>
                </div>

                <?php ActiveForm::end(); ?>

                <div class="text-center mt-4 small">
                    New here? <?= Html::a('Create an account', ['site/signup']) ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var \frontend\models\SignupForm $model */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$this->title = 'Create an account';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="row justify-content-center py-4">
    <div class="col-md-6 col-lg-5">
        <div class="card">
            <div class="card-body p-4 p-lg-5">
                <div class="text-center mb-4">
                    <div style="font-size: 2.4rem;">🍯</div>
                    <h1 class="h4 fw-bold mb-1"><?= Html::encode($this->title) ?></h1>
                    <p class="text-muted small mb-0">Register to order honey and track your purchases.</p>
                </div>

                <?php $form = ActiveForm::begin(['id' => 'form-signup']); ?>

                <?= $form->field($model, 'username')->textInput(['autofocus' => true, 'placeholder' => 'Your username'])
                    ->label('Username') ?>

                <?= $form->field($model, 'email')->textInput(['placeholder' => 'email@example.com'])
                    ->label('Email') ?>

                <?= $form->field($model, 'password')->passwordInput(['placeholder' => 'At least 8 characters'])
                    ->label('Password') ?>

                <div class="d-grid mt-3">
                    <?= Html::submitButton('Create account', ['class' => 'btn btn-honey btn-lg', 'name' => 'signup-button']) ?>
                </div>

                <?php ActiveForm::end(); ?>

                <div class="text-center mt-4 small">
                    Already have an account? <?= Html::a('Log in', ['site/login']) ?>
                </div>
            </div>
        </div>
    </div>
</div>

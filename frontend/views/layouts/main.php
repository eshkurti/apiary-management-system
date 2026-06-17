<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var string $content */

use common\widgets\Alert;
use frontend\assets\AppAsset;
use frontend\components\Cart;
use yii\bootstrap5\Breadcrumbs;
use yii\helpers\Html;
use yii\helpers\Url;

AppAsset::register($this);
$this->registerCsrfMetaTags();

$user       = Yii::$app->user;
$cartCount  = Cart::count();
$accountUrl = $user->isGuest ? Url::to(['/site/login']) : Url::to(['/account/index']);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" class="h-100" data-bs-theme="light">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php $this->head() ?>
    <title><?= Html::encode($this->title ? $this->title . ' · ' : '') ?>Honigmanufaktur Lindenhof</title>
</head>
<body class="d-flex flex-column h-100" data-bs-theme="light">
<?php $this->beginBody() ?>

<header class="shop-navbar">
    <div class="container d-flex align-items-center justify-content-between py-2">
        <a href="<?= Url::to(['/shop/index']) ?>" class="shop-brand">
            <span class="brand-mark">🍯</span>
            <span>Honigmanufaktur Lindenhof<small>Honey from Landkreis Hof</small></span>
        </a>
        <nav class="d-flex align-items-center gap-1">
            <a href="<?= Url::to(['/shop/index']) ?>" class="shop-nav-link">Shop</a>
            <a href="<?= $accountUrl ?>" class="shop-nav-link">My Account</a>
            <a href="<?= Url::to(['/cart/index']) ?>" class="shop-nav-link">
                🛒 Cart
                <?php if ($cartCount > 0): ?>
                    <span class="cart-badge"><?= $cartCount ?></span>
                <?php endif ?>
            </a>
            <?php if (!$user->isGuest): ?>
                <?= Html::beginForm(['/site/logout'], 'post', ['class' => 'd-inline ms-1']) ?>
                <?= Html::submitButton('Logout', ['class' => 'btn btn-sm btn-outline-honey']) ?>
                <?= Html::endForm() ?>
            <?php endif ?>
        </nav>
    </div>
</header>

<main id="main" class="flex-grow-1 py-4" role="main">
    <div class="container">
        <?php if (!empty($this->params['breadcrumbs'])): ?>
            <?= Breadcrumbs::widget(['links' => $this->params['breadcrumbs']]) ?>
        <?php endif ?>
        <?= Alert::widget() ?>
        <?= $content ?>
    </div>
</main>

<footer class="shop-footer py-4 mt-auto">
    <div class="container d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <div class="fw-bold">Honigmanufaktur Lindenhof</div>
            <div class="tagline">Naturreiner Honig aus dem Landkreis Hof · Pure honey, fully traceable from hive to jar.</div>
        </div>
        <div class="small">&copy; <?= date('Y') ?> Honigmanufaktur Lindenhof</div>
    </div>
</footer>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage();

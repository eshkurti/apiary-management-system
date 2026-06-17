<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var string $content */

use common\widgets\Alert;
use yii\bootstrap5\Breadcrumbs;
use yii\helpers\Html;
use yii\helpers\Url;

$this->render('_head');

$user = Yii::$app->user;

/**
 * Renders a single sidebar link, highlighting it when the current route matches.
 *
 * @param string $label
 * @param array  $route
 * @param string $icon  Bootstrap-icon-style unicode glyph
 */
$navLink = static function (string $label, array $route, string $icon = '•', array $matchActions = []): string {
    $current     = Yii::$app->controller->route;            // e.g. 'compliance/gate'
    $target      = ltrim($route[0], '/');                   // e.g. 'compliance/release-gate'
    $curParts    = explode('/', $current, 2);
    $tgtParts    = explode('/', $target, 2);
    $sameModule  = ($curParts[0] ?? '') === ($tgtParts[0] ?? '');
    // For single-link controllers, the controller match is enough so that sub-views
    // (view/create/update) keep the parent highlighted. For controllers with several
    // nav entries, $matchActions narrows the highlight to this link's own actions.
    $isActive = $sameModule
        && (empty($matchActions) || in_array($curParts[1] ?? 'index', $matchActions, true));
    $class = 'nav-link sidebar-link' . ($isActive ? ' active' : '');
    return Html::a(
        '<span class="sidebar-icon">' . $icon . '</span> ' . Html::encode($label),
        $route,
        ['class' => $class],
    );
};
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" class="h-100">
<head>
    <?php $this->head() ?>
    <title><?= Html::encode($this->title) ?> · Lindenhof Admin</title>
</head>
<body class="d-flex flex-column h-100">
<?php $this->beginBody() ?>

<nav id="lin-sidebar" data-bs-theme="dark">
    <?= Html::a('Honigmanufaktur Lindenhof<small>Apiary Management</small>', ['/dashboard/index'], ['class' => 'brand']) ?>

    <?php if ($user->can('viewDashboard')): ?>
        <div class="nav-section">Overview</div>
        <?= $navLink('Dashboard', ['/dashboard/index'], '📊') ?>
    <?php endif ?>

    <div class="nav-section">Production Management</div>
    <?php if ($user->can('manageCompanyProfile')): ?>
        <?= $navLink('Company Profile', ['/company-profile/update'], '🏢') ?>
    <?php endif ?>
    <?php if ($user->can('manageApiaryStands')): ?>
        <?= $navLink('Apiary Stands', ['/apiary-stand/index'], '📍') ?>
    <?php endif ?>
    <?php if ($user->can('viewColonies')): ?>
        <?= $navLink('Colonies', ['/colony/index'], '🐝') ?>
        <?= $navLink('Inspections', ['/inspection/index'], '🔍') ?>
        <?= $navLink('Treatments', ['/treatment/index'], '💊') ?>
    <?php endif ?>
    <?php if ($user->can('recordHarvest') || $user->can('completeBatchDetails') || $user->can('evaluateReleaseGate')): ?>
        <?= $navLink('Batches', ['/batch/index'], '🍯') ?>
    <?php endif ?>

    <div class="nav-section">Compliance</div>
    <?php if ($user->can('evaluateReleaseGate')): ?>
        <?= $navLink('Release Gate', ['/compliance/release-gate'], '✅', ['release-gate', 'gate', 'release']) ?>
    <?php endif ?>
    <?php if ($user->can('exportBestandsbuch')): ?>
        <?= $navLink('Bestandsbuch Export', ['/compliance/bestandsbuch'], '📒', ['bestandsbuch']) ?>
    <?php endif ?>
    <?php if ($user->can('exportStockkarte')): ?>
        <?= $navLink('Stockkarte Export', ['/compliance/stockkarte'], '🗂️', ['stockkarte']) ?>
    <?php endif ?>
    <?php if ($user->can('recallTrace')): ?>
        <?= $navLink('Recall Trace', ['/compliance/recall'], '🔎', ['recall']) ?>
    <?php endif ?>

    <div class="nav-section">Ecommerce</div>
    <?php if ($user->can('manageProducts')): ?>
        <?= $navLink('Products', ['/product/index'], '📦') ?>
    <?php endif ?>
    <?php if ($user->can('manageOrders')): ?>
        <?= $navLink('Orders', ['/order/index'], '🧾') ?>
    <?php endif ?>
    <?php if ($user->can('manageCustomers')): ?>
        <?= $navLink('Customers', ['/customer/index'], '👥') ?>
    <?php endif ?>
</nav>

<div id="lin-content" class="d-flex flex-column flex-grow-1" data-bs-theme="light">
    <div id="lin-topbar" class="d-flex justify-content-between align-items-center">
        <div class="text-muted small"><?= Html::encode((string) $this->title) ?></div>
        <div>
            <?php if (!$user->isGuest): ?>
                <span class="text-muted small me-3">
                    <?= Html::encode($user->identity?->username) ?>
                </span>
                <?= Html::beginForm(['/site/logout'], 'post', ['class' => 'd-inline']) ?>
                <?= Html::submitButton('Logout', ['class' => 'btn btn-sm btn-outline-secondary']) ?>
                <?= Html::endForm() ?>
            <?php endif ?>
        </div>
    </div>

    <main id="main" class="flex-grow-1 p-4" role="main">
        <?php if (!empty($this->params['breadcrumbs'])): ?>
            <?= Breadcrumbs::widget(['links' => $this->params['breadcrumbs']]) ?>
        <?php endif ?>
        <?= Alert::widget() ?>
        <?= $content ?>
    </main>

    <footer class="py-3 px-4 text-muted small border-top bg-white">
        &copy; <?= date('Y') ?> Honigmanufaktur Lindenhof · Apiary Management System
    </footer>
</div>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage();

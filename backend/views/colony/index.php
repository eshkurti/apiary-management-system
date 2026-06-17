<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */

use common\models\Colony;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\helpers\Html;

$this->title = 'Colonies';
$this->params['breadcrumbs'][] = $this->title;

$canManage = Yii::$app->user->can('manageColonies');
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Colonies <small class="text-muted fs-6">(Bienenvölker)</small></h1>
    <?php if ($canManage): ?>
        <?= Html::a('+ Register Colony', ['create'], ['class' => 'btn btn-warning']) ?>
    <?php endif ?>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'tableOptions' => ['class' => 'table table-striped table-hover align-middle'],
            'columns' => [
                'colony_code',
                [
                    'label' => 'Apiary Stand',
                    'value' => static fn (Colony $m): string => $m->apiaryStand->stand_code ?? '—',
                ],
                'queen_year',
                [
                    'attribute' => 'status',
                    'value' => static fn (Colony $m): string => ucfirst($m->status),
                ],
                [
                    'label' => 'Withdrawal',
                    'format' => 'raw',
                    'value' => static function (Colony $m): string {
                        if ($m->isWithdrawalCleared()) {
                            return '<span class="badge bg-success">Clear</span>';
                        }
                        return '<span class="badge bg-warning text-dark">Wartezeit until '
                            . Html::encode((string) $m->getLatestWartezeitExpiry()) . '</span>';
                    },
                ],
                [
                    'label' => 'Disease',
                    'format' => 'raw',
                    'value' => static fn (Colony $m): string => $m->disease_flag
                        ? '<span class="badge bg-danger">Flagged</span>'
                        : '<span class="badge bg-light text-muted">None</span>',
                ],
                [
                    'class' => ActionColumn::class,
                    'template' => '{view} {stockkarte}',
                    'buttons' => [
                        'stockkarte' => static fn (string $url, Colony $m): string => Html::a(
                            '📇',
                            ['stockkarte', 'id' => $m->id],
                            ['title' => 'Stockkarte', 'data-pjax' => 0],
                        ),
                    ],
                ],
            ],
        ]) ?>
    </div>
</div>

<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */

use backend\components\StatusBadge;
use common\models\Batch;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\helpers\Html;

$this->title = 'Release Gate';
$this->params['breadcrumbs'][] = 'Compliance';
$this->params['breadcrumbs'][] = $this->title;
?>
<h1 class="h3 mb-1">Release Gate <small class="text-muted fs-6">(Freigabeprüfung)</small></h1>
<p class="text-muted">
    Every batch must pass all five statutory conditions before it can be released for sale.
    Open a batch to see which conditions pass and which are blocking release.
</p>

<div class="card shadow-sm">
    <div class="card-body">
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'tableOptions' => ['class' => 'table table-striped table-hover align-middle'],
            'columns' => [
                'lot_number',
                'harvest_date',
                'honey_variety',
                [
                    'label' => 'Stand',
                    'value' => static fn (Batch $m): string => $m->apiaryStand->stand_code ?? '—',
                ],
                [
                    'label' => 'Gate',
                    'format' => 'raw',
                    'value' => static function (Batch $m): string {
                        $passed = 0;
                        foreach ($m->getReleaseGateChecks() as $check) {
                            $passed += $check['passed'] ? 1 : 0;
                        }
                        $cls = $passed === 5 ? 'bg-success' : 'bg-warning text-dark';
                        return '<span class="badge ' . $cls . '">' . $passed . ' / 5 passed</span>';
                    },
                ],
                [
                    'attribute' => 'status',
                    'format' => 'raw',
                    'value' => static fn (Batch $m): string => StatusBadge::html($m->status),
                ],
                [
                    'class' => ActionColumn::class,
                    'template' => '{gate}',
                    'buttons' => [
                        'gate' => static fn (string $url, Batch $m): string => Html::a(
                            'Open gate',
                            ['gate', 'id' => $m->id],
                            ['class' => 'btn btn-sm btn-outline-secondary', 'data-pjax' => 0],
                        ),
                    ],
                ],
            ],
        ]) ?>
    </div>
</div>

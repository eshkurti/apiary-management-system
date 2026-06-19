<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var int $pendingCount */
/** @var int $reviewCount */

use backend\components\StatusBadge;
use common\models\Batch;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\helpers\Html;

$this->title = 'Release Gate';
$this->params['breadcrumbs'][] = 'Compliance';
$this->params['breadcrumbs'][] = $this->title;

$total = $pendingCount + $reviewCount;
?>
<h1 class="h3 mb-1">Release Gate <small class="text-muted fs-6">(Freigabeprüfung)</small></h1>
<p class="text-muted">
    Batches that need a compliance decision. Open a batch to see which conditions pass
    and which are blocking release.
</p>

<?php if ($total === 0): ?>
    <div class="card shadow-sm">
        <div class="card-body text-center text-muted py-5">
            No batches currently require compliance review.
        </div>
    </div>
<?php else: ?>
    <p class="fw-semibold">
        <?= $pendingCount ?> batch<?= $pendingCount === 1 ? '' : 'es' ?> pending release
        ·
        <?= $reviewCount ?> batch<?= $reviewCount === 1 ? '' : 'es' ?> under review
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
<?php endif ?>

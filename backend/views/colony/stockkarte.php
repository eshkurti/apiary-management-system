<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Colony $model */

use yii\helpers\Html;

$this->title = 'Stockkarte — ' . $model->colony_code;
$this->params['breadcrumbs'][] = ['label' => 'Colonies', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->colony_code, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Stockkarte';

$entries = $model->getStockkarte();

$typeMeta = [
    'inspection' => ['Inspection', 'bg-info text-dark', '/inspection/view'],
    'treatment'  => ['Treatment', 'bg-warning text-dark', '/treatment/view'],
    'harvest'    => ['Harvest', 'bg-success', '/batch/view'],
];
?>
<h1 class="h3 mb-1">Stockkarte <small class="text-muted fs-6">(digital hive card)</small></h1>
<p class="text-muted">Read-only chronological history for colony <?= Html::encode($model->colony_code) ?>.</p>

<div class="row">
    <div class="col-md-4">
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <h2 class="h6 text-uppercase text-muted">Colony</h2>
                <dl class="row mb-0">
                    <dt class="col-6">Code</dt><dd class="col-6"><?= Html::encode($model->colony_code) ?></dd>
                    <dt class="col-6">Status</dt><dd class="col-6"><?= Html::encode(ucfirst($model->status)) ?></dd>
                    <dt class="col-6">Queen year</dt><dd class="col-6"><?= Html::encode((string) $model->queen_year) ?></dd>
                    <dt class="col-6">Stand</dt><dd class="col-6"><?= Html::encode($model->apiaryStand->stand_code ?? '—') ?></dd>
                </dl>
                <hr>
                <?php if ($model->isWithdrawalCleared()): ?>
                    <span class="badge bg-success">Withdrawal clear</span>
                <?php else: ?>
                    <span class="badge bg-warning text-dark">
                        Wartezeit until <?= Html::encode((string) $model->getLatestWartezeitExpiry()) ?>
                    </span>
                <?php endif ?>
                <?php if ($model->disease_flag): ?>
                    <span class="badge bg-danger">Disease flagged</span>
                <?php endif ?>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-body">
                <?php if (empty($entries)): ?>
                    <p class="text-muted mb-0">No inspections, treatments, or harvests recorded yet.</p>
                <?php else: ?>
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr><th>Date</th><th>Type</th><th>Summary</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($entries as $entry): ?>
                            <?php
                            [$label, $badge, $route] = $typeMeta[$entry['type']];
                            $record = $entry['record'];
                            $summary = match ($entry['type']) {
                                'inspection' => 'Brood ' . ($record->brood_pattern_score ?? '–') . '/5'
                                    . ($record->disease_indicators ? ' · ' . Html::encode($record->disease_indicators) : ''),
                                'treatment'  => Html::encode($record->product_name)
                                    . ' · Wartezeit → ' . Html::encode($record->wartezeit_expiry),
                                'harvest'    => Html::encode($record->lot_number) . ' · '
                                    . Html::encode((string) $record->harvest_quantity_kg) . ' kg',
                            };
                            ?>
                            <tr>
                                <td><?= Html::encode($entry['date']) ?></td>
                                <td><span class="badge <?= $badge ?>"><?= $label ?></span></td>
                                <td><?= $summary ?></td>
                                <td class="text-end">
                                    <?= Html::a('Open', [$route, 'id' => $record->id], ['class' => 'btn btn-sm btn-outline-secondary']) ?>
                                </td>
                            </tr>
                        <?php endforeach ?>
                        </tbody>
                    </table>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>

<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var int|string|null $colonyId */

use common\models\Colony;
use yii\helpers\Html;

$this->title = 'Stockkarte Export';
$this->params['breadcrumbs'][] = 'Compliance';
$this->params['breadcrumbs'][] = $this->title;

$colonies = Colony::find()->with('apiaryStand')->orderBy(['colony_code' => SORT_ASC])->all();
?>
<h1 class="h3 mb-1">Stockkarte Export</h1>
<p class="text-muted">
    Generate the complete chronological colony record — all inspections, treatments and harvests
    with full field values and submitter identity — as a UTF-8 CSV.
</p>

<div class="card shadow-sm" style="max-width: 680px;">
    <div class="card-body">
        <?= Html::beginForm(['stockkarte'], 'get') ?>
            <div class="mb-3">
                <label class="form-label" for="colony_id">Colony</label>
                <select class="form-select" id="colony_id" name="colony_id" required>
                    <option value="">— Select a colony —</option>
                    <?php foreach ($colonies as $c): ?>
                        <option value="<?= $c->id ?>" <?= (string) $colonyId === (string) $c->id ? 'selected' : '' ?>>
                            <?= Html::encode($c->colony_code . ' (' . ($c->apiaryStand->stand_code ?? '—') . ')') ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </div>

            <button type="submit" name="export" value="1" class="btn btn-warning">Download CSV</button>
        <?= Html::endForm() ?>
    </div>
</div>

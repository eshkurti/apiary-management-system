<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var int|string|null $standId */
/** @var string $dateFrom */
/** @var string $dateTo */

use common\models\ApiaryStand;
use yii\helpers\Html;

$this->title = 'Bestandsbuch Export';
$this->params['breadcrumbs'][] = 'Compliance';
$this->params['breadcrumbs'][] = $this->title;

$stands = ApiaryStand::find()->orderBy(['stand_code' => SORT_ASC])->all();
?>
<h1 class="h3 mb-1">Bestandsbuch Export</h1>
<p class="text-muted">
    Generate the legally required treatment ledger for a selected apiary stand and date range
    (EU Reg. 2019/6 Art. 108(2)). The export is a UTF-8 CSV with byte-order mark and carries the
    company identity and the stand's Veterinäramt registration number in its header.
</p>

<div class="card shadow-sm" style="max-width: 680px;">
    <div class="card-body">
        <?= Html::beginForm(['bestandsbuch'], 'get') ?>
            <div class="mb-3">
                <label class="form-label" for="stand_id">Apiary Stand</label>
                <select class="form-select" id="stand_id" name="stand_id" required>
                    <option value="">— Select a stand —</option>
                    <?php foreach ($stands as $s): ?>
                        <option value="<?= $s->id ?>" <?= (string) $standId === (string) $s->id ? 'selected' : '' ?>>
                            <?= Html::encode($s->stand_code . ' — ' . $s->name) ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="date_from">From date</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" value="<?= Html::encode($dateFrom) ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="date_to">To date</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" value="<?= Html::encode($dateTo) ?>">
                </div>
            </div>

            <p class="form-text">Leave dates blank to export the full history for the stand.</p>

            <button type="submit" name="export" value="1" class="btn btn-warning">Download CSV</button>
        <?= Html::endForm() ?>
    </div>
</div>

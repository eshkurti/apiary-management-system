<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var int|string|null $colonyId */
/** @var int|string|null $standId */

use common\models\ApiaryStand;
use common\models\Colony;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

$this->title = 'Stockkarte Export';
$this->params['breadcrumbs'][] = 'Compliance';
$this->params['breadcrumbs'][] = $this->title;

$stands = ApiaryStand::find()->orderBy(['stand_code' => SORT_ASC])->all();

// Pre-seed the colony dropdown when a stand is already chosen (re-render),
// so a selection survives before the AJAX runs.
$colonyOptions = [];
if ($standId !== null && $standId !== '') {
    $colonyOptions = Colony::find()
        ->where(['apiary_stand_id' => (int) $standId])
        ->orderBy(['colony_code' => SORT_ASC])
        ->all();
}

$coloniesUrl = Url::to(['colonies-for-stand']);
?>
<h1 class="h3 mb-1">Stockkarte Export</h1>
<p class="text-muted">
    Generate the complete chronological colony record — all inspections, treatments, harvests and
    feeding events with full field values and submitter identity — as a print-ready PDF or UTF-8 CSV.
</p>

<div class="card shadow-sm" style="max-width: 680px;">
    <div class="card-body">
        <?= Html::beginForm(['stockkarte'], 'get') ?>

            <div class="mb-3">
                <label class="form-label" for="stand_id">Apiary Stand</label>
                <select class="form-select" id="stand_id" name="stand_id">
                    <option value="">— Select a stand —</option>
                    <?php foreach ($stands as $s): ?>
                        <option value="<?= $s->id ?>" <?= (string) $standId === (string) $s->id ? 'selected' : '' ?>>
                            <?= Html::encode($s->stand_code . ' — ' . $s->name) ?>
                        </option>
                    <?php endforeach ?>
                </select>
                <div class="form-text">Select a stand to load its colonies, or to export every colony at once.</div>
            </div>

            <div class="mb-3">
                <label class="form-label" for="colony_id">Colony</label>
                <select class="form-select" id="colony_id" name="colony_id">
                    <option value="">— Select a stand first —</option>
                    <?php foreach ($colonyOptions as $c): ?>
                        <option value="<?= $c->id ?>" <?= (string) $colonyId === (string) $c->id ? 'selected' : '' ?>>
                            <?= Html::encode($c->colony_code) ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </div>

            <div class="mb-2 fw-semibold small text-muted">Export one colony</div>
            <div class="d-flex gap-2 mb-3">
                <button type="submit" name="format" value="pdf" class="btn btn-warning">Download PDF</button>
                <button type="submit" name="format" value="csv" class="btn btn-outline-secondary">Download CSV</button>
            </div>

            <hr>

            <div class="mb-2 fw-semibold small text-muted">Export all colonies at the selected stand</div>
            <div class="d-flex gap-2">
                <button type="submit" name="stand_export" value="pdf" class="btn btn-warning">All colonies — PDF</button>
                <button type="submit" name="stand_export" value="csv" class="btn btn-outline-secondary">All colonies — CSV</button>
            </div>

        <?= Html::endForm() ?>
    </div>
</div>

<?php
$coloniesUrlJs = Json::htmlEncode($coloniesUrl);
$currentColony = Json::htmlEncode((int) ($colonyId ?? 0));
$js = <<<JS
(function () {
    var coloniesUrl   = {$coloniesUrlJs};
    var currentColony = {$currentColony};

    var \$stand  = $('#stand_id');
    var \$colony = $('#colony_id');

    function loadColonies(standId, preselect) {
        if (!standId) {
            \$colony.empty().append('<option value="">— Select a stand first —</option>');
            return;
        }
        \$.getJSON(coloniesUrl, { standId: standId }, function (rows) {
            \$colony.empty().append('<option value="">— Select a colony —</option>');
            rows.forEach(function (c) {
                var label = c.colony_code;
                if (c.status !== 'active') { label += ' [' + c.status + ']'; }
                var \$opt = $('<option>').val(c.id).text(label);
                if (preselect && parseInt(c.id, 10) === parseInt(preselect, 10)) {
                    \$opt.prop('selected', true);
                }
                \$colony.append(\$opt);
            });
        });
    }

    \$stand.on('change', function () {
        loadColonies($(this).val(), null);
    });

    // On load, if a stand is already selected, keep its colony list (and the
    // current colony) in sync.
    if (\$stand.val()) {
        loadColonies(\$stand.val(), currentColony);
    }
})();
JS;
$this->registerJs($js);

<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Batch[] $batches */

use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

$this->title = 'New Product';
$this->params['breadcrumbs'][] = ['label' => 'Products', 'url' => ['index']];
$this->params['breadcrumbs'][] = 'New';

$options = [];
foreach ($batches as $b) {
    $options[$b->id] = $b->lot_number . ' — ' . $b->honey_variety
        . ' (' . max(0, $b->remainingUnits()) . ' units left)';
}
$createUrl = Url::to(['create']);
?>
<h1 class="h3 mb-1"><?= $this->title ?></h1>
<p class="text-muted">
    A product is created from a released batch. Choose a batch with units still available,
    then set its price and how many units to list.
</p>

<div class="card shadow-sm" style="max-width: 720px;">
    <div class="card-body">
        <label class="form-label" for="batch-picker">Source batch</label>
        <?php if (empty($options)): ?>
            <div class="alert alert-info mb-0">
                No released batches currently have units available to list.
                Release a batch and record its packaged unit count first.
            </div>
        <?php else: ?>
            <select id="batch-picker" class="form-select">
                <option value="">— Select a released batch —</option>
                <?php foreach ($options as $id => $label): ?>
                    <option value="<?= $id ?>"><?= Html::encode($label) ?></option>
                <?php endforeach ?>
            </select>
            <div class="form-text">Only released batches with units remaining appear here.</div>
        <?php endif ?>
    </div>
</div>

<div id="product-form-container" class="mt-3"></div>

<?php
$createUrlJs = Json::htmlEncode($createUrl);
$js = <<<JS
(function () {
    var url        = {$createUrlJs};
    var \$picker   = $('#batch-picker');
    var \$container = $('#product-form-container');

    \$picker.on('change', function () {
        var id = $(this).val();
        if (!id) { \$container.empty(); return; }
        \$container.html('<div class="text-muted">Loading…</div>');
        // Same action, same form fragment as arriving from the batch view.
        $.get(url, { batch_id: id }, function (html) {
            \$container.html(html);
        });
    });
})();
JS;
$this->registerJs($js);

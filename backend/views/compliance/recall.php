<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var string $searchType */
/** @var string $term */
/** @var common\models\Batch[] $batches */
/** @var array<int, array{order:string, date:string, customer:string, status:string, lot:string, quantity:int}> $orders */
/** @var bool $searched */
/** @var bool $notFound */

use backend\components\StatusBadge;
use yii\helpers\Html;

$this->title = 'Recall Trace';
$this->params['breadcrumbs'][] = 'Compliance';
$this->params['breadcrumbs'][] = $this->title;
?>
<h1 class="h3 mb-1">Recall Trace</h1>
<p class="text-muted">
    Trace a colony or batch to every customer order it reached. The trace covers all fulfilment
    stages, including delivered orders.
</p>

<div class="card shadow-sm mb-4" style="max-width: 720px;">
    <div class="card-body">
        <?= Html::beginForm(['recall'], 'get', ['class' => 'row g-2 align-items-end']) ?>
            <div class="col-md-4">
                <label class="form-label" for="search_type">Search by</label>
                <select class="form-select" id="search_type" name="search_type">
                    <option value="lot" <?= $searchType === 'lot' ? 'selected' : '' ?>>Batch lot number</option>
                    <option value="colony" <?= $searchType === 'colony' ? 'selected' : '' ?>>Colony code</option>
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label" for="term">Search term</label>
                <input type="text" class="form-control" id="term" name="term"
                       value="<?= Html::encode($term) ?>" placeholder="e.g. LIN-2026-001 or LIN-C-001" required>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-warning w-100">Trace</button>
            </div>
        <?= Html::endForm() ?>
    </div>
</div>

<?php if ($searched): ?>
    <?php if ($notFound): ?>
        <div class="alert alert-warning">
            No <?= $searchType === 'colony' ? 'colony' : 'batch' ?> found matching
            <strong><?= Html::encode($term) ?></strong>.
        </div>
    <?php else: ?>

        <?php if ($searchType === 'colony'): ?>
            <h2 class="h5">Batches sourced from <?= Html::encode($term) ?></h2>
            <?php if (empty($batches)): ?>
                <p class="text-muted">This colony has not contributed to any batch.</p>
            <?php else: ?>
                <div class="card shadow-sm mb-4">
                    <table class="table table-striped mb-0 align-middle">
                        <thead><tr><th>Lot Number</th><th>Harvest Date</th><th>Variety</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php foreach ($batches as $b): ?>
                            <tr>
                                <td><?= Html::a(Html::encode($b->lot_number), ['/batch/view', 'id' => $b->id]) ?></td>
                                <td><?= Html::encode($b->harvest_date) ?></td>
                                <td><?= Html::encode($b->honey_variety) ?></td>
                                <td><?= Html::encode(ucwords(str_replace('_', ' ', $b->status))) ?></td>
                            </tr>
                        <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            <?php endif ?>
        <?php endif ?>

        <h2 class="h5">Affected orders</h2>
        <?php if (empty($orders)): ?>
            <div class="alert alert-info">
                No customer orders contain product from
                <?= $searchType === 'colony' ? 'this colony' : 'this batch' ?>.
            </div>
        <?php else: ?>
            <div class="alert alert-danger">
                <strong><?= count($orders) ?></strong> affected order line(s) found.
                These customers received product that must be recalled.
            </div>
            <div class="card shadow-sm">
                <table class="table table-striped mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Order Reference</th>
                            <th>Order Date</th>
                            <th>Customer</th>
                            <th>Lot Number</th>
                            <th>Quantity</th>
                            <th>Fulfilment Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($orders as $row): ?>
                        <tr>
                            <td><?= Html::encode($row['order']) ?></td>
                            <td><?= Html::encode($row['date']) ?></td>
                            <td><?= Html::encode($row['customer']) ?></td>
                            <td><?= Html::encode($row['lot']) ?></td>
                            <td><?= Html::encode((string) $row['quantity']) ?></td>
                            <td><?= StatusBadge::html($row['status']) ?></td>
                        </tr>
                    <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        <?php endif ?>

    <?php endif ?>
<?php endif ?>

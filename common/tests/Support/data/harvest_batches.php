<?php

declare(strict_types=1);

// Three batches, all harvested 2026-05-01 at stand 1. Each has every non-
// withdrawal release-gate field already passing (water 18%, variety + origin +
// best-before present, HACCP confirmed) so the only variable across them is the
// source colony / status.
$common = [
    'harvest_date'        => '2026-05-01',
    'apiary_stand_id'     => 1,
    'harvest_quantity_kg' => '25.00',
    'honey_variety'       => 'Blütenhonig',
    'water_content'       => '18.00',
    'best_before_date'    => '2027-05-01',
    'origin_statement'    => 'Honey from Germany (Bavaria, Landkreis Hof)',
    'haccp_confirmed'     => 1,
    'created_at'          => 1700000000,
    'updated_at'          => 1700000000,
    'created_by'          => 1,
];

return [
    // Clean batch: linked to colony 2 only → all five checks pass.
    'batchClean' => array_merge($common, [
        'id'         => 1,
        'lot_number' => 'LIN-2026-001',
        'status'     => 'pending_release',
    ]),
    // Blocked batch: linked to colony 1 → fails the withdrawal check.
    'batchBlocked' => array_merge($common, [
        'id'         => 2,
        'lot_number' => 'LIN-2026-002',
        'status'     => 'pending_release',
    ]),
    // Already-released batch: linked to colony 2, with a published product.
    // packaged_unit_count gives it available units so a further product can be
    // created from it (the seeded product allocates only part of the yield).
    'batchReleased' => array_merge($common, [
        'id'                  => 3,
        'lot_number'          => 'LIN-2026-003',
        'status'              => 'released',
        'packaged_unit_count' => 100,
        'released_at'         => 1700500000,
        'released_by'         => 1,
    ]),
];

<?php

declare(strict_types=1);

// The order line links order 1 back to its source batch (id 3) via the
// permanently snapshotted lot number LIN-2026-003 — this is what the recall
// trace searches on.
return [
    'item1' => [
        'id'           => 1,
        'order_id'     => 1,
        'product_id'   => 1,
        'batch_id'     => 3,
        'lot_number'   => 'LIN-2026-003',
        'product_name' => 'Lindenhof Blütenhonig 500g',
        'quantity'     => 2,
        'unit_price'   => '6.50',
        'line_total'   => '13.00',
    ],
];

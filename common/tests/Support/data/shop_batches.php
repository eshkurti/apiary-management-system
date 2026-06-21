<?php

declare(strict_types=1);

// One released batch with a known lot number. Its provenance fields are what
// the product-detail traceability panel displays.
return [
    'batch1' => [
        'id'                  => 1,
        'lot_number'          => 'LIN-2026-101',
        'harvest_date'        => '2026-05-01',
        'apiary_stand_id'     => 1,
        'harvest_quantity_kg' => '40.00',
        'honey_variety'       => 'Blütenhonig',
        'water_content'       => '18.00',
        'best_before_date'    => '2027-05-01',
        'origin_statement'    => 'Honey from Germany (Bavaria, Landkreis Hof)',
        'haccp_confirmed'     => 1,
        'status'              => 'released',
        'released_at'         => 1700500000,
        'released_by'         => 1,
        'created_at'          => 1700000000,
        'updated_at'          => 1700000000,
        'created_by'          => 1,
    ],
];

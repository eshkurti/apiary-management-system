<?php

declare(strict_types=1);

// Two colonies at stand 1, both starting without a disease flag.
//   colony1 (LIN-C-001) — carries a treatment whose Wartezeit still spans the
//                         batch harvest date, so it blocks release.
//   colony2 (LIN-C-002) — its treatment Wartezeit has already cleared by the
//                         harvest date, so it is eligible.
return [
    'colony1' => [
        'id'                     => 1,
        'colony_code'            => 'LIN-C-001',
        'apiary_stand_id'        => 1,
        'queen_year'             => 2025,
        'status'                 => 'active',
        'annual_varroa_treated'  => 0,
        'annual_trachea_treated' => 0,
        'disease_flag'           => 0,
        'created_at'             => 1700000000,
        'updated_at'             => 1700000000,
        'created_by'             => 1,
    ],
    'colony2' => [
        'id'                     => 2,
        'colony_code'            => 'LIN-C-002',
        'apiary_stand_id'        => 1,
        'queen_year'             => 2025,
        'status'                 => 'active',
        'annual_varroa_treated'  => 0,
        'annual_trachea_treated' => 0,
        'disease_flag'           => 0,
        'created_at'             => 1700000000,
        'updated_at'             => 1700000000,
        'created_by'             => 1,
    ],
];

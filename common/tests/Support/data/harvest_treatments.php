<?php

declare(strict_types=1);

// Batches in this suite harvest on 2026-05-01.
//   treatColony1: applied 2026-04-20, Wartezeit until 2026-05-20 (AFTER harvest)
//                 → colony 1 is within withdrawal at harvest → blocks release.
//   treatColony2: applied 2026-03-01, Wartezeit until 2026-03-22 (BEFORE harvest)
//                 → colony 2 has cleared withdrawal at harvest → eligible.
return [
    'treatColony1' => [
        'id'                          => 1,
        'colony_id'                   => 1,
        'apiary_stand_id'             => 1,
        'treatment_type'              => 'varroa',
        'product_name'                => 'AMO Varroa',
        'pharmaceutical_batch_number' => 'CHG-0001',
        'quantity_per_colony'         => '50 ml',
        'supplier_name'               => 'Imkereibedarf Hof',
        'supplier_address'            => 'Hauptstraße 1, 95028 Hof',
        'application_date'            => '2026-04-20',
        'colonies_treated_at_stand'   => 1,
        'withdrawal_days'             => 30,
        'wartezeit_expiry'            => '2026-05-20',
        'treatment_duration_days'     => 1,
        'operator_name'               => 'erau',
        'created_at'                  => 1700000000,
        'updated_at'                  => 1700000000,
        'created_by'                  => 1,
    ],
    'treatColony2' => [
        'id'                          => 2,
        'colony_id'                   => 2,
        'apiary_stand_id'             => 1,
        'treatment_type'              => 'varroa',
        'product_name'                => 'AMO Varroa',
        'pharmaceutical_batch_number' => 'CHG-0002',
        'quantity_per_colony'         => '50 ml',
        'supplier_name'               => 'Imkereibedarf Hof',
        'supplier_address'            => 'Hauptstraße 1, 95028 Hof',
        'application_date'            => '2026-03-01',
        'colonies_treated_at_stand'   => 1,
        'withdrawal_days'             => 21,
        'wartezeit_expiry'            => '2026-03-22',
        'treatment_duration_days'     => 1,
        'operator_name'               => 'erau',
        'created_at'                  => 1700000000,
        'updated_at'                  => 1700000000,
        'created_by'                  => 1,
    ],
];

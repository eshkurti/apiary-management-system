<?php

declare(strict_types=1);

// A single stand is needed only to satisfy the released batch's
// apiary_stand_id foreign key.
return [
    'stand1' => [
        'id'                   => 1,
        'stand_code'           => 'LIN-S-001',
        'name'                 => 'Lindenhof Hauptstand',
        'latitude'             => '50.3214000',
        'longitude'            => '11.9112000',
        'landkreis'            => 'Hof',
        'authority_reg_number' => 'VET-HOF-001',
        'is_active'            => 1,
        'created_at'           => 1700000000,
        'updated_at'           => 1700000000,
        'created_by'           => 1,
    ],
];

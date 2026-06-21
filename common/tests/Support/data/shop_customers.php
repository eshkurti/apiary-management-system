<?php

declare(strict_types=1);

// CRM records for the two shop accounts.
//   retail    — no stored address, so the missing-address test starts blank.
//   wholesale — complete address + a 10-unit minimum order quantity.
return [
    'retail' => [
        'id'                 => 1,
        'user_id'            => 1,
        'name'               => 'Retail Buyer',
        'email'              => 'customer@example.com',
        'country'            => 'Germany',
        'is_wholesale'       => 0,
        'min_order_quantity' => null,
        'is_active'          => 1,
        'created_at'         => 1700000000,
        'updated_at'         => 1700000000,
    ],
    'wholesale' => [
        'id'                 => 2,
        'user_id'            => 2,
        'name'               => 'Wholesale Buyer',
        'email'              => 'wholesale@example.com',
        'company'            => 'Imkerei Großhandel GmbH',
        'address'            => 'Großhandelweg 5',
        'postcode'           => '95028',
        'city'               => 'Hof',
        'country'            => 'Germany',
        'is_wholesale'       => 1,
        'min_order_quantity' => 10,
        'is_active'          => 1,
        'created_at'         => 1700000000,
        'updated_at'         => 1700000000,
    ],
];

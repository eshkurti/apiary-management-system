<?php

declare(strict_types=1);

// Two published products from the released batch.
//   product1 — used for the retail checkout (stock 10).
//   product2 — used for the wholesale-minimum test (stock 20).
return [
    'product1' => [
        'id'                 => 1,
        'batch_id'           => 1,
        'name'               => 'Lindenhof Blütenhonig 500g',
        'description'        => 'Retail checkout test product.',
        'price'              => '5.00',
        'wholesale_price'    => null,
        'stock_quantity'     => 10,
        'is_published'       => 1,
        'review_unpublished' => 0,
        'created_at'         => 1700000000,
        'updated_at'         => 1700000000,
        'created_by'         => 1,
    ],
    'product2' => [
        'id'                 => 2,
        'batch_id'           => 1,
        'name'               => 'Lindenhof Blütenhonig 1kg',
        'description'        => 'Wholesale minimum test product.',
        'price'              => '5.00',
        'wholesale_price'    => null,
        'stock_quantity'     => 20,
        'is_published'       => 1,
        'review_unpublished' => 0,
        'created_at'         => 1700000000,
        'updated_at'         => 1700000000,
        'created_by'         => 1,
    ],
];

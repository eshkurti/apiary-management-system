<?php

declare(strict_types=1);

// A published product drawn from the released batch (id 3). When colony 2 is
// disease-flagged this product must be pulled from the shop.
return [
    'product1' => [
        'id'                 => 1,
        'batch_id'           => 3,
        'name'               => 'Lindenhof Blütenhonig 500g',
        'description'        => 'Test product for the disease-flag cascade.',
        'price'              => '5.00',
        'wholesale_price'    => null,
        'stock_quantity'     => 10,
        'is_published'       => 1,
        'review_unpublished' => 0,
        'created_at'         => 1700000000,
        'updated_at'         => 1700000000,
        'created_by'         => 1,
    ],
];

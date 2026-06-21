<?php

declare(strict_types=1);

// One delivered order for customer 1, used as the end point of the recall
// traceability chain. Truncating the order table here also clears any demo-seed
// orders so the recall trace returns only this fixture order.
return [
    'order1' => [
        'id'               => 1,
        'customer_id'      => 1,
        'order_number'     => 'ORD-2026-9001',
        'order_date'       => '2026-05-10',
        'total_amount'     => '13.00',
        'status'           => 'delivered',
        'shipping_address' => 'Hans Müller, Honigweg 1, 95028 Hof, Germany',
        'created_at'       => 1700400000,
        'updated_at'       => 1700400000,
    ],
];

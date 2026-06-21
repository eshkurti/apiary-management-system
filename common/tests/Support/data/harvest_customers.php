<?php

declare(strict_types=1);

// A single customer who placed the order traced in the recall test. user_id is
// left null (no shop login needed) to avoid colliding with the admin user.
return [
    'customer1' => [
        'id'                 => 1,
        'user_id'            => null,
        'name'              => 'Hans Müller',
        'email'             => 'hans.mueller@example.com',
        'country'           => 'Germany',
        'is_wholesale'      => 0,
        'is_active'         => 1,
        'created_at'        => 1700000000,
        'updated_at'        => 1700000000,
    ],
];

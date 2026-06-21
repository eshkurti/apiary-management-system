<?php

declare(strict_types=1);

// Two shop accounts. Both are active (status 10) so they satisfy
// User::findIdentity. Passwords are not used — the tests log in via
// amLoggedInAs — but a valid hash is provided for completeness.
return [
    'customer' => [
        'id'            => 1,
        'username'      => 'shopcustomer',
        'auth_key'      => 'tUu1qHcde0diwUol3xeI-18MuHkkprQI',
        // password_0
        'password_hash' => '$2y$13$nJ1WDlBaGcbCdbNC5.5l4.sgy.OMEKCqtDQOdQ2OWpgiKRWYyzzne',
        'email'         => 'customer@example.com',
        'status'        => 10,
        'created_at'    => 1700000000,
        'updated_at'    => 1700000000,
    ],
    'wholesale' => [
        'id'            => 2,
        'username'      => 'wholesalebuyer',
        'auth_key'      => 'tUu1qHcde0diwUol3xeI-18MuHkkpr99',
        // password_0
        'password_hash' => '$2y$13$nJ1WDlBaGcbCdbNC5.5l4.sgy.OMEKCqtDQOdQ2OWpgiKRWYyzzne',
        'email'         => 'wholesale@example.com',
        'status'        => 10,
        'created_at'    => 1700000000,
        'updated_at'    => 1700000000,
    ],
];

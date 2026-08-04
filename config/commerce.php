<?php

return [
    'company' => [
        'name' => env('COMMERCE_COMPANY_NAME', 'IGI Canada'),
        'address' => env('COMMERCE_COMPANY_ADDRESS', '966 Pantera Dr., Unit 7'),
        'city' => env('COMMERCE_COMPANY_CITY', 'Mississauga, ON L4W 2S1'),
        'country' => env('COMMERCE_COMPANY_COUNTRY', 'Canada'),
        'phone' => env('COMMERCE_COMPANY_PHONE', '905-625-8831'),
        'email' => env('COMMERCE_COMPANY_EMAIL', 'newigi@gmail.com'),
    ],
    'order_notification_email' => env('COMMERCE_ORDER_EMAIL', env('MAIL_FROM_ADDRESS')),
];

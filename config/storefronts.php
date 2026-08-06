<?php

return [
    'default_channel' => env('STOREFRONT_DEFAULT_CHANNEL', 'wholesale'),

    'admin_domain' => env('ADMIN_DOMAIN'),

    'wholesale' => [
        'domain' => env('WHOLESALE_DOMAIN', 'igicanada.ca'),
        'aliases' => array_filter(explode(',', (string) env('WHOLESALE_DOMAIN_ALIASES', 'www.igicanada.ca'))),
        'name' => 'IGI Canada',
    ],

    'retail' => [
        'domain' => env('RETAIL_DOMAIN', 'leatherwallets.ca'),
        'aliases' => array_filter(explode(',', (string) env('RETAIL_DOMAIN_ALIASES', 'www.leatherwallets.ca'))),
        'name' => 'Leather Wallets Canada',
    ],
];

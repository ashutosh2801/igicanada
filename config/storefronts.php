<?php

return [
    'default_channel' => env('STOREFRONT_DEFAULT_CHANNEL', 'wholesale'),

    'admin_domain' => env('ADMIN_DOMAIN'),

    // When set, legacy product images are served from this absolute base URL
    // (e.g. https://igicanada.ca). Leave empty in local development when the
    // legacy /upload folder is symlinked into public/ so images are served by
    // the local app instead.
    'legacy_asset_url' => env('LEGACY_ASSET_URL', 'https://igicanada.ca'),

    'wholesale' => [
        'domain' => env('WHOLESALE_DOMAIN', 'igicanada.ca'),
        'aliases' => array_filter(explode(',', (string) env('WHOLESALE_DOMAIN_ALIASES', 'www.igicanada.ca'))),
        'name' => 'IGI Canada',
    ],

    'retail' => [
        'domain' => env('RETAIL_DOMAIN', 'leatherwallets.ca'),
        'aliases' => array_filter(explode(',', (string) env('RETAIL_DOMAIN_ALIASES', 'www.leatherwallets.ca'))),
        // Additional domains that serve the retail storefront directly (no
        // canonical redirect), e.g. walletsandbelts.localhost.
        'siblings' => array_filter(explode(',', (string) env('RETAIL_DOMAIN_SIBLINGS', ''))),
        // Sibling retail domains that run as their own branded storefront with
        // separate homepage/branding settings. Maps domain → sales_channel value.
        'branded_domains' => [
            'walletsandbelts.com' => 'walletsandbelts',
        ],
        'name' => 'Leather Wallets Canada',
    ],

    // Channel → display metadata for the admin storefront switcher, "view site"
    // links and storefront fallbacks.
    'brands' => [
        'wholesale' => [
            'name' => 'IGI Canada',
            'domain' => env('WHOLESALE_DOMAIN', 'igicanada.ca'),
        ],
        'retail' => [
            'name' => 'Leather Wallets Canada',
            'domain' => env('RETAIL_DOMAIN', 'leatherwallets.ca'),
        ],
        'walletsandbelts' => [
            'name' => 'Wallets and Belts Canada',
            'domain' => 'walletsandbelts.com',
        ],
    ],
];

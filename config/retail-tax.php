<?php

return [
    'reviewed' => (bool) env('RETAIL_TAX_REVIEWED', false),

    // Enable only after confirming the business is registered to collect GST/HST.
    'gst_hst_registered' => (bool) env('RETAIL_GST_HST_REGISTERED', false),
    'registration_number' => env('RETAIL_GST_HST_NUMBER'),

    // Current GST/HST rates verified against CRA guidance in August 2026.
    'rates' => [
        'AB' => ['label' => 'GST', 'rate' => 0.05],
        'BC' => ['label' => 'GST', 'rate' => 0.05],
        'MB' => ['label' => 'GST', 'rate' => 0.05],
        'NB' => ['label' => 'HST', 'rate' => 0.15],
        'NL' => ['label' => 'HST', 'rate' => 0.15],
        'NS' => ['label' => 'HST', 'rate' => 0.14],
        'NT' => ['label' => 'GST', 'rate' => 0.05],
        'NU' => ['label' => 'GST', 'rate' => 0.05],
        'ON' => ['label' => 'HST', 'rate' => 0.13],
        'PE' => ['label' => 'HST', 'rate' => 0.15],
        'QC' => ['label' => 'GST', 'rate' => 0.05],
        'SK' => ['label' => 'GST', 'rate' => 0.05],
        'YT' => ['label' => 'GST', 'rate' => 0.05],
    ],
];

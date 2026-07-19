<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Prices include tax
    |--------------------------------------------------------------------------
    |
    | EU B2C convention (and legal requirement): displayed prices are
    | VAT-inclusive. The tax figure is therefore extracted from the price, not
    | added on top. See docs/platform-spec.md §8.
    |
    */

    'prices_include_tax' => true,

    'default_country' => 'MT',

    /*
    |--------------------------------------------------------------------------
    | Rate table — keyed by destination country, then tax class
    |--------------------------------------------------------------------------
    |
    | Resolved from destination + product tax class, never hardcoded to 18%.
    | Malta only today; when gymdog crosses the €10k EU distance-selling
    | threshold (OSS), other member states' rates are added here without
    | touching the calculator.
    |
    */

    'rates' => [
        'MT' => [
            'standard' => 0.18,
            'reduced' => 0.07,
            'zero' => 0.0,
        ],
    ],

    'fallback_rate' => 0.18,

];

<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default theme
    |--------------------------------------------------------------------------
    |
    | The theme used when a tenant has no `theme` set in its settings. Gymdog is
    | customer zero, so its look is the default the platform ships with.
    |
    */

    'default' => env('THEME_DEFAULT', 'gymdog'),

    /*
    |--------------------------------------------------------------------------
    | Fallback theme
    |--------------------------------------------------------------------------
    |
    | Every theme falls back to this one for any view it does not override, so a
    | new merchant's theme only needs to define what differs from the baseline.
    | Storefront views live under resources/views/themes/{theme}/.
    |
    */

    'fallback' => 'default',

    'path' => resource_path('views/themes'),

];

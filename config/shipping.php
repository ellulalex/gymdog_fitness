<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Malta shipping rule
    |--------------------------------------------------------------------------
    |
    | Flat €10 under €50, free at or above €50. Malta only for now — the
    | confirmed live rule (docs/site-audit.md). The threshold is checked
    | against the post-discount goods subtotal.
    |
    */

    'origin_country' => 'MT',

    'free_threshold_cents' => 5000,

    'flat_cents' => 1000,

    // Countries we currently ship to. Checkout blocks anything else.
    'countries' => ['MT'],

    // Shipping is taxed at this class's rate at the destination.
    'tax_class' => 'standard',

];

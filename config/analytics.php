<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Google Analytics 4
    |--------------------------------------------------------------------------
    |
    | Sets cookies, so under GDPR it needs consent. Loaded through Google
    | Consent Mode v2: the tag is always present but storage is denied until the
    | visitor accepts, which keeps us lawful while still giving Google modelled
    | data for the people who decline. Leave empty to disable entirely.
    |
    */

    'ga4_id' => env('GA4_MEASUREMENT_ID'),

    /*
    |--------------------------------------------------------------------------
    | Cloudflare Web Analytics
    |--------------------------------------------------------------------------
    |
    | Cookieless, so it needs no consent and counts every visitor — including
    | those who decline GA4. This is the reliable baseline; GA4 is the deep
    | ecommerce view. Leave empty if you enable automatic injection in the
    | Cloudflare dashboard instead (the site is proxied, so Cloudflare can add
    | the beacon at the edge with no code here).
    |
    */

    'cloudflare_token' => env('CLOUDFLARE_ANALYTICS_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Consent banner
    |--------------------------------------------------------------------------
    |
    | Only shown when a consent-requiring tag (GA4) is actually configured —
    | no point asking permission we don't need.
    |
    */

    'consent_cookie' => 'gymdog_consent',

];

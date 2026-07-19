<?php

return [

    /*
    |--------------------------------------------------------------------------
    | WooCommerce import (catalogue:import-woocommerce)
    |--------------------------------------------------------------------------
    |
    | Pulls products from the live WooCommerce Store API (public, no keys). The
    | public API exposes prices, sale prices, stock status, categories, images,
    | option terms and the real variation combinations — but NOT per-variant
    | price/stock/SKU or the brand taxonomy, so those are filled in here or left
    | for manual completion.
    |
    */

    'woocommerce' => [

        // child category slug => parent category slug (two levels, per the audit).
        'category_parents' => [
            'fingerless' => 'grips',
            'wrist-straps' => 'accessories',
        ],

        // WooCommerce attribute name => our option name.
        'attribute_names' => [
            'color' => 'Colour',
            'colour' => 'Colour',
            'sizes' => 'Size',
            'size' => 'Size',
        ],

        'colour_swatches' => [
            'Black' => '#111111', 'White' => '#f5f5f5', 'Grey' => '#9ca3af', 'Gray' => '#9ca3af',
            'Blue' => '#1e40af', 'Green' => '#16a34a', 'Red' => '#dc2626', 'Pink' => '#ec4899',
            'Purple' => '#7c3aed',
        ],

        // product slug => brand name. Best inference from product lines — CONFIRM.
        // The brand taxonomy isn't in the public API, so anything not listed here
        // is imported with no brand and flagged for review.
        'brand_map' => [
            'velites-hand-grips-all-terrain' => 'Velites',
            'velites-lifting-belt' => 'Velites',
            'jump-rope-fire-2-0' => 'Velites',
            'reyllen-gx-belt' => 'Reyllen',
            'condor-grips' => 'Picsil',
            'bumblebee-x2-gymnastic-grips-3-hole' => 'Picsil',
            'merlin-x3-gymnastic-grips-fingerless' => 'Picsil',
            'merlin-x4-gymnastic-grips-fingerless' => 'Picsil',
            'panda-x3-gymnastic-grips-3-hole' => 'Picsil',
            'hex-tech-knee-pads-5mm-0-2' => 'Picsil',
            'venta-x2-knee-sleeves-5mm' => 'Picsil',
        ],

        // Placeholder stock for in-stock variants (real quantities are entered by hand).
        'default_stock' => 10,
    ],

];

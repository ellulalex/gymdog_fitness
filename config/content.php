<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Auto-publish with a brake
    |--------------------------------------------------------------------------
    |
    | When false (the default, and the spec's recommendation for the first ~20
    | posts), generated posts land as drafts for a human to publish. When true,
    | they schedule for `brake_hours` ahead and auto-publish unless rejected —
    | you only act to *stop* something. See docs/platform-spec.md §9.
    |
    */

    'auto_publish' => (bool) env('CONTENT_AUTO_PUBLISH', false),

    'brake_hours' => (int) env('CONTENT_BRAKE_HOURS', 24),

    // Where the review/reject email is sent while the brake is on.
    'notify_email' => env('CONTENT_NOTIFY_EMAIL', env('ADMIN_EMAIL')),

    /*
    |--------------------------------------------------------------------------
    | Generation
    |--------------------------------------------------------------------------
    */

    'model' => env('CONTENT_MODEL', 'claude-opus-4-8'),

    // Headroom for a ~2000-word article plus meta + FAQ as JSON.
    'max_tokens' => (int) env('CONTENT_MAX_TOKENS', 8000),

    // Ground drafts in current facts via Claude's web search (freshness/accuracy
    // — important for event coverage). Bounded to a few searches per article.
    'web_search' => (bool) env('CONTENT_WEB_SEARCH', true),

    'target_words' => (int) env('CONTENT_TARGET_WORDS', 2000),

    // Falls back to the tenant's `brand_voice` setting when present.
    'brand_voice' => 'Knowledgeable, encouraging CrossFit coach writing for a Maltese audience. Practical, never hype.',

    /*
    |--------------------------------------------------------------------------
    | Quality gates
    |--------------------------------------------------------------------------
    */

    'min_words' => (int) env('CONTENT_MIN_WORDS', 400),

    /*
    |--------------------------------------------------------------------------
    | WordPress import (Phase 3 migration)
    |--------------------------------------------------------------------------
    |
    | Rules for `content:import-wordpress`. The eight CrossFit movement guides
    | live as WordPress *pages* but should become posts with type=guide (root
    | URLs preserved). Some slugs are dropped with a 301 instead (spec §10).
    |
    */

    'wordpress' => [

        // WordPress "pages" that are really guides → imported as type=guide posts.
        'guide_slugs' => [
            'the-power-clean',
            'weightlifting-in-crossfit',
            '9-foundational-movements',
            'thruster',
            'gymnastics-in-crossfit',
            'cardiovascular-fitness',
            'the-devils-press',
            'crossfit-boxes-in-malta',
        ],

        // Skipped on import (Ninetheme demo leftover, etc.).
        'skip_slugs' => ['about-me'],

        // 301s to create alongside the import.
        'redirects' => [
            '/about-me/' => '/about-us',
            '/category/uncategorized/' => '/blog',
        ],

        // Post categories to skip.
        'skip_categories' => ['uncategorized'],
    ],

    /*
    | Banned-claims filter — CRITICAL for a fitness site. Generated content must
    | not make medical claims, give dosage/treatment/injury advice, or promise
    | health outcomes. This is regulatory exposure, not just quality (spec §9).
    | A post tripping any pattern is never auto-published; it is flagged for
    | human review.
    */
    'banned_patterns' => [
        '/\bcures?\b/i',
        '/\btreats?\b/i',
        '/\bdiagnos(e|is|ing)\b/i',
        '/\bprevents? (disease|illness|cancer)\b/i',
        '/\bdosage\b/i',
        '/\bmg\b/i',
        '/\bprescrib/i',
        '/\bheals?\b/i',
        '/\bmedical(ly)?\b/i',
        '/\bsupplements? (cure|heal|treat)/i',
    ],

];

@php
    $brand = $tenant?->name ?? config('app.name');
    $url = url('/'.$post->slug);
    $desc = $post->meta_description ?: ($post->excerpt ?: Str::of(strip_tags($post->body))->limit(160));
    // Featured image first, else the first inline image, for og:image / Article image.
    $image = $post->featured_image;
    if (! $image && preg_match('/<img[^>]+src="([^"]+)"/i', (string) $post->body, $m)) {
        $image = $m[1];
    }
    if ($image) {
        $image = \Illuminate\Support\Str::startsWith($image, ['http://', 'https://']) ? $image : url($image);
    }

    $article = array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => $post->title,
        'description' => (string) $desc,
        'datePublished' => optional($post->published_at)->toAtomString(),
        'dateModified' => optional($post->updated_at)->toAtomString(),
        'image' => $image ? [$image] : null,
        'keywords' => $post->focus_keyword,
        'author' => ['@type' => $post->author ? 'Person' : 'Organization', 'name' => $post->author?->name ?? $brand],
        'publisher' => ['@type' => 'Organization', 'name' => $brand],
        'mainEntityOfPage' => $url,
    ]);

    $breadcrumb = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Blog', 'item' => route('blog')],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $post->title, 'item' => $url],
        ],
    ];

    $faqSchema = null;
    if (! empty($post->faq)) {
        $faqSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => collect($post->faq)->map(fn ($item) => [
                '@type' => 'Question',
                'name' => $item['question'] ?? '',
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['answer'] ?? ''],
            ])->all(),
        ];
    }

    $json = fn ($data) => json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
@endphp

<meta property="og:type" content="article">
<meta property="og:title" content="{{ $post->title }}">
<meta property="og:description" content="{{ $desc }}">
<meta property="og:url" content="{{ $url }}">
<meta property="og:site_name" content="{{ $brand }}">
@if ($image)<meta property="og:image" content="{{ $image }}">@endif
<meta name="twitter:card" content="{{ $image ? 'summary_large_image' : 'summary' }}">

<script type="application/ld+json">{!! $json($article) !!}</script>
<script type="application/ld+json">{!! $json($breadcrumb) !!}</script>
@if ($faqSchema)<script type="application/ld+json">{!! $json($faqSchema) !!}</script>@endif

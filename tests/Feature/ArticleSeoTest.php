<?php

use App\Models\Post;
use App\Models\Tenant;
use App\Support\Tenancy\TenantManager;

beforeEach(function () {
    $this->tenant = Tenant::create(['slug' => 'gymdog', 'name' => 'GymDog']);
    app(TenantManager::class)->set($this->tenant);
});

it('renders Article, Breadcrumb and FAQ structured data plus Open Graph', function () {
    $post = Post::factory()->create([
        'slug' => 'hyrox-vs-crossfit',
        'title' => 'Hyrox vs CrossFit',
        'meta_description' => 'How Hyrox and CrossFit differ.',
        'focus_keyword' => 'hyrox vs crossfit',
        'body' => '<p>Body <img src="https://example.com/hero.jpg"></p>',
        'status' => 'published',
        'published_at' => now()->subDay(),
        'faq' => [
            ['question' => 'Is Hyrox like CrossFit?', 'answer' => 'They overlap but differ.'],
        ],
    ]);

    $res = $this->get('/'.$post->slug)->assertOk();

    // JSON-LD blocks.
    $res->assertSee('"@type":"Article"', false)
        ->assertSee('"@type":"BreadcrumbList"', false)
        ->assertSee('"@type":"FAQPage"', false)
        ->assertSee('Is Hyrox like CrossFit?', false)
        // Open Graph + canonical.
        ->assertSee('property="og:title"', false)
        ->assertSee('og:image', false)                 // pulled from the body image
        ->assertSee('rel="canonical"', false)
        // Visible FAQ section.
        ->assertSee('Frequently asked questions');
});

it('omits FAQ schema when a post has no faq', function () {
    $post = Post::factory()->create(['slug' => 'plain', 'status' => 'published', 'published_at' => now()->subDay()]);

    $this->get('/'.$post->slug)
        ->assertOk()
        ->assertSee('"@type":"Article"', false)
        ->assertDontSee('"@type":"FAQPage"', false);
});

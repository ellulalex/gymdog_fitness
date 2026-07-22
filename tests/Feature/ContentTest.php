<?php

use App\Models\Page;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Redirect;
use App\Models\Tenant;
use App\Support\Tenancy\TenantManager;

beforeEach(function () {
    $this->tenant = Tenant::create(['slug' => 'gymdog', 'name' => 'GymDog']);
    app(TenantManager::class)->set($this->tenant);
});

it('renders a published page at the root and 404s a draft', function () {
    Page::factory()->create(['slug' => 'about-us', 'title' => 'About Us', 'body' => '<p>Hello Malta</p>']);
    Page::factory()->draft()->create(['slug' => 'secret-page']);

    $this->get('/about-us')->assertOk()->assertSee('Hello Malta', false);
    $this->get('/secret-page')->assertNotFound();
});

it('renders posts and crossfit guides at the root', function () {
    Post::factory()->create(['slug' => 'getting-started-in-crossfit', 'title' => 'Getting Started']);
    Post::factory()->guide()->create(['slug' => 'the-power-clean', 'title' => 'The Power Clean']);

    $this->get('/getting-started-in-crossfit')->assertOk()->assertSee('Getting Started');
    $this->get('/the-power-clean')->assertOk()->assertSee('The Power Clean')->assertSee('CrossFit guide');
});

it('lists articles on the blog but not guides', function () {
    Post::factory()->create(['title' => 'A Real Article', 'excerpt' => 'article-marker']);
    Post::factory()->guide()->create(['title' => 'A Movement Guide', 'excerpt' => 'guide-marker']);

    // Excerpts only render in the blog article cards (guides appear only as
    // titles in the nav), so they isolate the article list from the nav.
    $this->get('/blog')
        ->assertOk()
        ->assertSee('article-marker')
        ->assertDontSee('guide-marker');
});

it('lists a published article that has no published_at date', function () {
    // An older post with a real date, plus a just-published one with no date.
    Post::factory()->create(['title' => 'Dated Article', 'published_at' => now()->subMonth()]);
    Post::factory()->create(['title' => 'Undated Article', 'excerpt' => 'undated-marker', 'published_at' => null]);

    $res = $this->get('/blog')->assertOk()->assertSee('undated-marker');

    // COALESCE(published_at, created_at) keeps the newer undated post ahead of
    // the month-old dated one, instead of sorting NULLs to the very end.
    expect(strpos($res->getContent(), 'Undated Article'))
        ->toBeLessThan(strpos($res->getContent(), 'Dated Article'));
});

it('shows a post category page', function () {
    $category = PostCategory::create(['slug' => 'crossfit', 'name' => 'CrossFit']);
    $post = Post::factory()->create(['title' => 'Open Recap']);
    $post->categories()->attach($category);

    $this->get('/category/crossfit')->assertOk()->assertSee('Open Recap');
});

it('301s an old WordPress url via the redirects table', function () {
    Redirect::create(['from_path' => '/about-me/', 'to_path' => '/about-us', 'status_code' => 301]);

    $this->get('/about-me/')->assertRedirect('/about-us')->assertStatus(301);
});

it('serves an xml sitemap listing published content', function () {
    Page::factory()->create(['slug' => 'faq']);
    Post::factory()->create(['slug' => 'the-crossfit-open']);

    $response = $this->get('/sitemap.xml');
    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('xml');
    $response->assertSee('/faq', false)->assertSee('/the-crossfit-open', false);
});

it('does not let the root catch-all shadow app routes', function () {
    $this->get('/shop')->assertOk();      // shop, not a content lookup
    $this->get('/admin/login')->assertOk(); // Filament
});

it('lists movement guides in the header CrossFit menu', function () {
    Post::factory()->guide()->create(['slug' => 'the-power-clean', 'title' => 'The Power Clean']);
    Post::factory()->create(['title' => 'A Normal Article']); // not a guide

    // The shop page has the nav but no articles section, isolating the nav.
    $this->get('/shop')
        ->assertOk()
        ->assertSee('CrossFit', false)
        ->assertSee('The Power Clean')      // guide is in the nav
        ->assertDontSee('A Normal Article'); // articles are not
});

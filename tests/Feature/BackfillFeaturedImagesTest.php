<?php

use App\Models\Post;
use App\Models\Tenant;
use App\Support\Tenancy\TenantManager;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->tenant = Tenant::create(['slug' => 'gymdog', 'name' => 'GymDog']);
    app(TenantManager::class)->set($this->tenant);
    Storage::fake('public');
    config()->set('content.images.provider', 'unsplash');
    config()->set('services.unsplash.access_key', 'test-key');
    config()->set('services.pexels.key', null);
});

it('promotes an inline body image to the featured image', function () {
    Http::preventStrayRequests(); // body-image path must not hit the API
    $post = Post::factory()->create([
        'body' => '<p>Intro</p><img src="/storage/content-media/abc.jpg"><p>More</p>',
        'featured_image' => null,
    ]);

    $this->artisan('content:backfill-images')->assertSuccessful();

    expect($post->fresh()->featured_image)->toBe('/storage/content-media/abc.jpg');
});

it('fetches a stock photo when there is no body image', function () {
    Http::fake([
        'api.unsplash.com/search/photos*' => Http::response(['results' => [[
            'urls' => ['regular' => 'https://images.unsplash.com/p.jpg'],
            'user' => ['name' => 'Jane Doe'],
        ]]]),
        'images.unsplash.com/*' => Http::response('BYTES', 200),
    ]);
    $post = Post::factory()->create(['body' => '<p>No image here</p>', 'focus_keyword' => 'power clean', 'featured_image' => null]);

    $this->artisan('content:backfill-images')->assertSuccessful();

    $fresh = $post->fresh();
    expect($fresh->featured_image)->toContain('content-media')
        ->and($fresh->featured_image_credit)->toBe('Photo by Jane Doe on Unsplash');
});

it('leaves posts that already have a featured image untouched', function () {
    Http::preventStrayRequests();
    $post = Post::factory()->create(['featured_image' => '/existing.jpg', 'body' => '<p>text</p>']);

    $this->artisan('content:backfill-images')->assertSuccessful();

    expect($post->fresh()->featured_image)->toBe('/existing.jpg');
});

it('skips drafts unless --all is given', function () {
    Http::preventStrayRequests();
    $draft = Post::factory()->draft()->create(['body' => '<img src="/storage/x.jpg">', 'featured_image' => null]);

    $this->artisan('content:backfill-images')->assertSuccessful();
    expect($draft->fresh()->featured_image)->toBeNull();

    $this->artisan('content:backfill-images', ['--all' => true])->assertSuccessful();
    expect($draft->fresh()->featured_image)->toBe('/storage/x.jpg');
});

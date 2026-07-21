<?php

use App\Domain\Content\ImageFetcher;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    config()->set('content.images.provider', 'unsplash');
    config()->set('content.images.orientation', 'landscape');
    config()->set('services.unsplash.access_key', null);
    config()->set('services.pexels.key', null);
});

it('fetches, re-hosts and credits an Unsplash photo', function () {
    config()->set('services.unsplash.access_key', 'test-key');
    Http::fake([
        'api.unsplash.com/search/photos*' => Http::response(['results' => [[
            'urls' => ['regular' => 'https://images.unsplash.com/photo-1.jpg'],
            'user' => ['name' => 'Jane Doe'],
            'links' => ['download_location' => 'https://api.unsplash.com/photos/abc/download'],
        ]]]),
        'api.unsplash.com/photos/*' => Http::response(['url' => 'ok']),
        'images.unsplash.com/*' => Http::response('IMAGEBYTES', 200),
    ]);

    $image = app(ImageFetcher::class)->fetch('sled push gym');

    expect($image)->not->toBeNull()
        ->and($image->credit)->toBe('Photo by Jane Doe on Unsplash')
        ->and($image->url)->toContain('content-media');
    expect(Storage::disk('public')->allFiles('content-media'))->toHaveCount(1);
});

it('falls back to Pexels when Unsplash has no key', function () {
    config()->set('content.images.provider', 'pexels');
    config()->set('services.pexels.key', 'pex-key');
    Http::fake([
        'api.pexels.com/v1/search*' => Http::response(['photos' => [[
            'src' => ['large' => 'https://images.pexels.com/photo-2.jpg'],
            'photographer' => 'Sam Roe',
        ]]]),
        'images.pexels.com/*' => Http::response('IMAGEBYTES', 200),
    ]);

    $image = app(ImageFetcher::class)->fetch('rowing machine');

    expect($image?->credit)->toBe('Photo by Sam Roe on Pexels');
});

it('returns null (no request) when no provider key is configured', function () {
    Http::preventStrayRequests();

    expect(app(ImageFetcher::class)->fetch('anything'))->toBeNull();
});

it('returns null for an empty query', function () {
    expect(app(ImageFetcher::class)->fetch(''))->toBeNull()
        ->and(app(ImageFetcher::class)->fetch(null))->toBeNull();
});

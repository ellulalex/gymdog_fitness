<?php

use App\Domain\Content\WordPressImporter;
use App\Models\Page;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Redirect;
use App\Models\Tenant;
use App\Support\Tenancy\TenantManager;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->tenant = Tenant::create(['slug' => 'gymdog', 'name' => 'GymDog']);
    app(TenantManager::class)->set($this->tenant);
});

/** The default WordPress fixture. Called per-test so stubs don't merge across setups. */
function fakeWordPress(): void
{
    Http::fake([
        '*/wp-json/wp/v2/categories*' => Http::response([
            ['id' => 5, 'slug' => 'crossfit', 'name' => 'CrossFit'],
            ['id' => 1, 'slug' => 'uncategorized', 'name' => 'Uncategorized'],
        ], 200, ['X-WP-TotalPages' => 1]),

        '*/wp-json/wp/v2/pages*' => Http::response([
            ['slug' => 'about-us', 'title' => ['rendered' => 'About Us'], 'content' => ['rendered' => '<p>Malta shop</p>'], 'status' => 'publish', 'date_gmt' => '2023-01-01T00:00:00'],
            ['slug' => 'the-power-clean', 'title' => ['rendered' => 'The Power Clean'], 'content' => ['rendered' => '<p>guide body</p>'], 'status' => 'publish', 'date_gmt' => '2023-02-01T00:00:00'],
            ['slug' => 'about-me', 'title' => ['rendered' => 'About Me'], 'content' => ['rendered' => '<p>demo</p>'], 'status' => 'publish'],
        ], 200, ['X-WP-TotalPages' => 1]),

        '*/wp-json/wp/v2/posts*' => Http::response([
            ['slug' => 'getting-started-in-crossfit', 'title' => ['rendered' => 'Getting Started &amp; More'], 'excerpt' => ['rendered' => '<p>An intro excerpt.</p>'], 'content' => ['rendered' => '<p>body</p>'], 'status' => 'publish', 'date_gmt' => '2023-03-01T00:00:00', 'categories' => [5, 1]],
        ], 200, ['X-WP-TotalPages' => 1]),
    ]);
}

it('imports pages, guides and posts with the right types and URLs', function () {
    fakeWordPress();
    $counts = (new WordPressImporter('https://gymdog.fitness'))->import();

    $about = Page::where('slug', 'about-us')->first();
    expect($about)->not->toBeNull()
        ->and($about->title)->toBe('About Us')
        ->and($about->status)->toBe('published');

    $guide = Post::where('slug', 'the-power-clean')->first();
    expect($guide)->not->toBeNull()->and($guide->type)->toBe('guide');

    $post = Post::where('slug', 'getting-started-in-crossfit')->first();
    expect($post->type)->toBe('post')
        ->and($post->title)->toBe('Getting Started & More')
        ->and($post->categories->pluck('slug')->all())->toBe(['crossfit']);

    expect($counts['pages'])->toBe(1)
        ->and($counts['guides'])->toBe(1)
        ->and($counts['posts'])->toBe(1);
});

it('skips demo pages and the uncategorized category, and creates the 301s', function () {
    fakeWordPress();
    (new WordPressImporter('https://gymdog.fitness'))->import();

    expect(Page::where('slug', 'about-me')->exists())->toBeFalse()
        ->and(Post::where('slug', 'about-me')->exists())->toBeFalse()
        ->and(PostCategory::where('slug', 'uncategorized')->exists())->toBeFalse()
        ->and(Redirect::where('from_path', '/about-me/')->first()->to_path)->toBe('/about-us')
        ->and(Redirect::where('from_path', '/category/uncategorized/')->exists())->toBeTrue();
});

it('is idempotent — re-running does not duplicate', function () {
    fakeWordPress();
    (new WordPressImporter('https://gymdog.fitness'))->import();
    (new WordPressImporter('https://gymdog.fitness'))->import();

    expect(Page::where('slug', 'about-us')->count())->toBe(1)
        ->and(Post::where('slug', 'getting-started-in-crossfit')->count())->toBe(1);
});

it('writes nothing on a dry run', function () {
    fakeWordPress();
    $this->artisan('content:import-wordpress', ['--url' => 'https://gymdog.fitness', '--dry-run' => true])
        ->assertSuccessful();

    expect(Page::count())->toBe(0)->and(Post::count())->toBe(0);
});

it('fails without a url', function () {
    $this->artisan('content:import-wordpress')->assertFailed();
});

it('re-hosts WordPress images into local storage and rewrites the body', function () {
    Storage::fake('public');
    $imageUrl = 'https://gymdog.fitness/wp-content/uploads/2023/01/pic.jpg';

    Http::fake([
        '*/wp-json/wp/v2/categories*' => Http::response([], 200, ['X-WP-TotalPages' => 1]),
        '*/wp-json/wp/v2/pages*' => Http::response([
            ['slug' => 'faq', 'title' => ['rendered' => 'FAQ'], 'status' => 'publish', 'content' => ['rendered' => '<p><img src="'.$imageUrl.'" srcset="https://gymdog.fitness/wp-content/uploads/2023/01/pic-300x200.jpg 300w" sizes="100vw"></p>']],
        ], 200, ['X-WP-TotalPages' => 1]),
        '*/wp-json/wp/v2/posts*' => Http::response([], 200, ['X-WP-TotalPages' => 1]),
        'https://gymdog.fitness/wp-content/*' => Http::response('FAKE-IMAGE-BYTES', 200, ['Content-Type' => 'image/jpeg']),
    ]);

    $counts = (new WordPressImporter('https://gymdog.fitness'))->import();

    $body = Page::where('slug', 'faq')->first()->body;
    expect($body)->toContain('/storage/content-media/')
        ->and($body)->not->toContain('wp-content')
        ->and($counts['media'])->toBe(1);
    Storage::disk('public')->assertExists('content-media/'.sha1($imageUrl).'.jpg');
});

it('can skip media re-hosting when asked', function () {
    Http::fake([
        '*/wp-json/wp/v2/categories*' => Http::response([], 200, ['X-WP-TotalPages' => 1]),
        '*/wp-json/wp/v2/pages*' => Http::response([
            ['slug' => 'faq', 'title' => ['rendered' => 'FAQ'], 'status' => 'publish', 'content' => ['rendered' => '<p><img src="https://gymdog.fitness/wp-content/uploads/pic.jpg"></p>']],
        ], 200, ['X-WP-TotalPages' => 1]),
        '*/wp-json/wp/v2/posts*' => Http::response([], 200, ['X-WP-TotalPages' => 1]),
    ]);

    (new WordPressImporter('https://gymdog.fitness', rehostMedia: false))->import();

    expect(Page::where('slug', 'faq')->first()->body)->toContain('wp-content');
});

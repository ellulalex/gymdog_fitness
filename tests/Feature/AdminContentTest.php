<?php

use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Models\Post;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\TenantManager;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    $this->tenant = Tenant::create(['slug' => 'gymdog', 'name' => 'GymDog']);
    app(TenantManager::class)->set($this->tenant);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs(User::factory()->create());
});

it('renders the content admin pages', function (string $url) {
    $this->get($url)->assertOk();
})->with([
    '/admin/pages',
    '/admin/pages/create',
    '/admin/posts',
    '/admin/posts/create',
    '/admin/post-categories',
    '/admin/post-categories/create',
    '/admin/redirects',
    '/admin/redirects/create',
    '/admin/topics',
    '/admin/topics/create',
]);

it('bulk-publishes selected posts, backdating nothing and skipping live ones', function () {
    $draft = Post::factory()->draft()->create();
    $backdated = Post::factory()->create([
        'status' => 'draft',
        'published_at' => now()->subMonths(3),   // genuine past date — must be kept
    ]);
    $future = Post::factory()->create([
        'status' => 'draft',
        'published_at' => now()->addWeek(),      // would stay hidden — must be replaced
    ]);
    $live = Post::factory()->create(['status' => 'published', 'published_at' => now()->subDay()]);
    $liveDate = $live->published_at;

    Livewire::test(ListPosts::class)
        ->callTableBulkAction('publish', [$draft, $backdated, $future, $live])
        ->assertHasNoErrors();

    expect($draft->fresh()->status)->toBe('published')
        ->and($draft->fresh()->published_at)->not->toBeNull()
        // A genuine past date survives, so old articles don't jump the blog order.
        ->and($backdated->fresh()->published_at->isSameDay(now()->subMonths(3)))->toBeTrue()
        // A future date is replaced, otherwise scopePublished() would hide it.
        ->and($future->fresh()->published_at->isFuture())->toBeFalse()
        // Already-live posts are untouched.
        ->and($live->fresh()->published_at->eq($liveDate))->toBeTrue();

    // All four are now visible on the storefront.
    expect(Post::published()->count())->toBe(4);
});

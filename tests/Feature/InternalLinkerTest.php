<?php

use App\Domain\Content\InternalLinker;
use App\Models\Post;
use App\Models\Tenant;
use App\Support\Tenancy\TenantManager;

beforeEach(function () {
    $this->tenant = Tenant::create(['slug' => 'gymdog', 'name' => 'GymDog']);
    app(TenantManager::class)->set($this->tenant);
    config()->set('content.internal_links.enabled', true);
    config()->set('content.internal_links.max', 4);
});

function linkPost(array $attrs = []): Post
{
    return Post::factory()->create(array_merge([
        'slug' => 'jump-rope-guide',
        'title' => 'Jump Rope Guide',
        'focus_keyword' => 'jump rope',
    ], $attrs));
}

it('links the first mention of an existing post keyword', function () {
    linkPost();

    $result = app(InternalLinker::class)->link('<p>Learn to jump rope like a pro today.</p>');

    expect($result['count'])->toBe(1)
        ->and($result['html'])->toContain('<a href="/jump-rope-guide">jump rope</a>');
});

it('links each target at most once and respects the max', function () {
    linkPost();
    config()->set('content.internal_links.max', 1);

    $result = app(InternalLinker::class)->link('<p>jump rope here and jump rope again.</p>');

    expect($result['count'])->toBe(1)
        ->and(substr_count($result['html'], '<a href="/jump-rope-guide"'))->toBe(1);
});

it('never links text inside a heading', function () {
    linkPost();

    $result = app(InternalLinker::class)->link('<h2>How to jump rope</h2><p>Some other text.</p>');

    expect($result['count'])->toBe(0)
        ->and($result['html'])->not->toContain('<a ');
});

it('does not re-link text already inside an anchor', function () {
    linkPost();

    $html = '<p>See our <a href="/x">jump rope</a> notes.</p>';
    $result = app(InternalLinker::class)->link($html);

    expect($result['count'])->toBe(0)
        ->and($result['html'])->not->toContain('/jump-rope-guide');
});

it('excludes the post being generated', function () {
    $self = linkPost();

    $result = app(InternalLinker::class)->link('<p>talk about jump rope</p>', $self->id);

    expect($result['count'])->toBe(0);
});

it('does nothing when disabled', function () {
    linkPost();
    config()->set('content.internal_links.enabled', false);

    $result = app(InternalLinker::class)->link('<p>jump rope</p>');

    expect($result['count'])->toBe(0)
        ->and($result['html'])->toBe('<p>jump rope</p>');
});

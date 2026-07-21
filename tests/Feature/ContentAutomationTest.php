<?php

use App\Domain\Content\ContentGenerator;
use App\Domain\Content\GeneratedDraft;
use App\Domain\Content\GenerationPipeline;
use App\Domain\Content\QualityGates;
use App\Mail\GeneratedPostReview;
use App\Models\Post;
use App\Models\Tenant;
use App\Models\Topic;
use App\Support\Tenancy\TenantManager;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\Support\FakeContentGenerator;

beforeEach(function () {
    $this->tenant = Tenant::create(['slug' => 'gymdog', 'name' => 'GymDog']);
    app(TenantManager::class)->set($this->tenant);
    Mail::fake();
});

function useGenerator(?GeneratedDraft $draft = null): void
{
    app()->instance(ContentGenerator::class, new FakeContentGenerator($draft));
}

function longBody(string $extra = ''): string
{
    return '<p>'.$extra.str_repeat('practical grip and rope training tip. ', 80).'</p>';
}

it('returns null when the topic queue is empty', function () {
    useGenerator();

    expect(app(GenerationPipeline::class)->run())->toBeNull();
});

it('generates a draft post, tags it and marks the topic used (human-approval mode)', function () {
    config()->set('content.auto_publish', false);
    useGenerator();
    $topic = Topic::factory()->create(['title' => 'jump rope basics']);

    $post = app(GenerationPipeline::class)->run();

    expect($post->source)->toBe('generated')
        ->and($post->status)->toBe('draft')            // human-approval mode
        ->and($post->generation_meta['topic_id'])->toBe($topic->id)
        ->and($post->generation_meta['model'])->toBe(config('content.model'))
        ->and($topic->fresh()->status)->toBe('used');
    Mail::assertNothingSent();
});

it('persists the SEO fields (meta, keyword, FAQ) and image query from the draft', function () {
    config()->set('content.auto_publish', false);
    useGenerator();
    Topic::factory()->create(['title' => 'jump rope basics']);

    $post = app(GenerationPipeline::class)->run();

    expect($post->meta_title)->toBe('Guide to jump rope basics')
        ->and($post->meta_description)->toBe('A practical guide to jump rope basics.')
        ->and($post->focus_keyword)->toBe('jump rope basics')
        ->and($post->faq)->toBe([['question' => 'Where do I start?', 'answer' => 'With the basics.']])
        ->and($post->generation_meta['image_query'])->toBe('crossfit training')
        ->and($post->generation_meta['word_count'])->toBeGreaterThan(0);
});

it('applies internal links and a featured image to a generated post', function () {
    Illuminate\Support\Facades\Storage::fake('public');
    config()->set('content.auto_publish', false);
    config()->set('content.images.provider', 'unsplash');
    config()->set('services.unsplash.access_key', 'test-key');
    Illuminate\Support\Facades\Http::fake([
        'api.unsplash.com/search/photos*' => Illuminate\Support\Facades\Http::response(['results' => [[
            'urls' => ['regular' => 'https://images.unsplash.com/p.jpg'],
            'user' => ['name' => 'Jane Doe'],
        ]]]),
        'images.unsplash.com/*' => Illuminate\Support\Facades\Http::response('BYTES', 200),
    ]);

    // An existing published post the new article can link to.
    Post::factory()->create(['slug' => 'jump-rope-guide', 'title' => 'Jump Rope Guide', 'focus_keyword' => 'jump rope']);

    useGenerator(new GeneratedDraft(
        title: 'Conditioning Basics',
        excerpt: 'A guide.',
        body: longBody('Start with jump rope work. '),
        focusKeyword: 'conditioning',
        imageQuery: 'jump rope',
    ));
    Topic::factory()->create();

    $post = app(GenerationPipeline::class)->run();

    expect($post->featured_image)->not->toBeNull()
        ->and($post->featured_image_credit)->toBe('Photo by Jane Doe on Unsplash')
        ->and($post->body)->toContain('<a href="/jump-rope-guide">jump rope</a>')
        ->and($post->generation_meta['internal_links'])->toBe(1);
});

it('schedules a clean draft with the brake and emails a reject link when auto-publish is on', function () {
    config()->set('content.auto_publish', true);
    config()->set('content.brake_hours', 24);
    config()->set('content.notify_email', 'alex@example.com');
    useGenerator();
    Topic::factory()->create();

    $post = app(GenerationPipeline::class)->run();

    expect($post->status)->toBe('scheduled')
        ->and($post->published_at->isFuture())->toBeTrue()
        ->and($post->generation_meta['violations'])->toBe([]);
    Mail::assertSent(GeneratedPostReview::class);
});

it('never schedules a draft that trips the banned-claims gate', function () {
    config()->set('content.auto_publish', true);
    useGenerator(new GeneratedDraft('This supplement cures injuries', 'x', longBody('It can cure and treat you. ')));
    Topic::factory()->create();

    $post = app(GenerationPipeline::class)->run();

    expect($post->status)->toBe('draft')
        ->and($post->generation_meta['violations'])->not->toBeEmpty();
    Mail::assertNothingSent();
});

it('flags a too-short draft', function () {
    $gates = app(QualityGates::class);

    $violations = $gates->check(new GeneratedDraft('Short', 'x', '<p>too short</p>'));

    expect($violations)->toContain('Below the minimum word count.');
});

it('flags a duplicate title', function () {
    Post::factory()->create(['title' => 'Getting Started in CrossFit']);
    $gates = app(QualityGates::class);

    $violations = $gates->check(new GeneratedDraft('Getting Started in CrossFit', 'x', longBody()));

    expect($violations)->toContain('Duplicate of an existing post title.');
});

it('publishes due scheduled posts but not future or rejected ones', function () {
    $due = Post::factory()->create(['status' => 'scheduled', 'published_at' => now()->subHour(), 'source' => 'generated']);
    $future = Post::factory()->create(['status' => 'scheduled', 'published_at' => now()->addDay(), 'source' => 'generated']);
    $rejected = Post::factory()->create(['status' => 'rejected', 'published_at' => now()->subHour(), 'source' => 'generated']);

    $this->artisan('content:publish-due')->assertSuccessful();

    expect($due->fresh()->status)->toBe('published')
        ->and($future->fresh()->status)->toBe('scheduled')
        ->and($rejected->fresh()->status)->toBe('rejected');
});

it('rejects a scheduled post via the signed link and keeps it from publishing', function () {
    $post = Post::factory()->create(['status' => 'scheduled', 'published_at' => now()->addDay(), 'source' => 'generated']);

    $url = URL::signedRoute('content.reject', ['post' => $post->id]);
    $this->get($url)->assertOk();

    expect($post->fresh()->status)->toBe('rejected');

    // Even once due, a rejected post is not published.
    $post->update(['published_at' => now()->subHour()]);
    $this->artisan('content:publish-due');
    expect($post->fresh()->status)->toBe('rejected');
});

it('refuses an unsigned reject link', function () {
    $post = Post::factory()->create(['status' => 'scheduled', 'source' => 'generated']);

    $this->get("/content/{$post->id}/reject")->assertForbidden();
});

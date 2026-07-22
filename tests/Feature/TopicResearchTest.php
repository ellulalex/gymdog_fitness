<?php

use App\Domain\Content\ProposedTopic;
use App\Domain\Content\TopicResearcher;
use App\Domain\Content\TopicResearchPipeline;
use App\Models\Post;
use App\Models\Tenant;
use App\Models\Topic;
use App\Support\Tenancy\TenantManager;
use Tests\Support\FakeTopicResearcher;

beforeEach(function () {
    $this->tenant = Tenant::create(['slug' => 'gymdog', 'name' => 'GymDog']);
    app(TenantManager::class)->set($this->tenant);
});

function useResearcher(array $proposals): FakeTopicResearcher
{
    $fake = new FakeTopicResearcher($proposals);
    app()->instance(TopicResearcher::class, $fake);

    return $fake;
}

it('enqueues new topics with keyword and appended positions', function () {
    Topic::factory()->create(['title' => 'Existing topic', 'position' => 7]);

    useResearcher([
        new ProposedTopic('Hyrox pacing strategy', 'How to pace each station', 'hyrox pacing'),
        new ProposedTopic('Choosing a jump rope', 'Buyer guide', 'best jump rope'),
    ]);

    $result = app(TopicResearchPipeline::class)->run(2);

    expect($result['added'])->toBe(2)
        ->and($result['skipped'])->toBe(0);

    $new = Topic::where('title', 'Hyrox pacing strategy')->first();
    expect($new->focus_keyword)->toBe('hyrox pacing')
        ->and($new->status)->toBe('queued')
        ->and($new->position)->toBe(8)                     // appended after position 7
        ->and(Topic::where('title', 'Choosing a jump rope')->first()->position)->toBe(9);
});

it('skips proposals that duplicate an existing topic or post', function () {
    Topic::factory()->create(['title' => 'Hyrox pacing strategy']);
    Post::factory()->create(['title' => 'The Devil Press explained']);

    useResearcher([
        new ProposedTopic('Hyrox pacing strategy'),           // dupes a queued topic
        new ProposedTopic('the devil press explained'),       // dupes a post (case-insensitive)
        new ProposedTopic('Wall balls for beginners'),        // new
    ]);

    $result = app(TopicResearchPipeline::class)->run(3);

    expect($result['added'])->toBe(1)
        ->and($result['skipped'])->toBe(2)
        ->and($result['titles'])->toBe(['Wall balls for beginners']);
});

it('deduplicates within the same batch', function () {
    useResearcher([
        new ProposedTopic('Rowing technique tips'),
        new ProposedTopic('Rowing technique tips'),           // same batch duplicate
    ]);

    $result = app(TopicResearchPipeline::class)->run(2);

    expect($result['added'])->toBe(1)
        ->and(Topic::where('title', 'Rowing technique tips')->count())->toBe(1);
});

it('passes existing titles to the researcher so it can avoid them', function () {
    Topic::factory()->create(['title' => 'Existing queued topic']);
    Post::factory()->create(['title' => 'Existing post title']);
    $fake = useResearcher([]);

    app(TopicResearchPipeline::class)->run(4);

    expect($fake->calledWithCount)->toBe(4)
        ->and($fake->calledWithAvoid)->toContain('Existing queued topic')
        ->and($fake->calledWithAvoid)->toContain('Existing post title');
});

it('tops the queue up to --ensure only when it is below target', function () {
    Topic::factory()->count(3)->create(['status' => 'queued']);
    $fake = useResearcher([new ProposedTopic('Filler topic one'), new ProposedTopic('Filler topic two')]);

    // Queue has 3, ensure 5 → research exactly 2.
    $this->artisan('content:research-topics', ['--ensure' => 5])->assertSuccessful();
    expect($fake->calledWithCount)->toBe(2);
});

it('skips the API call when the queue already meets --ensure', function () {
    Topic::factory()->count(6)->create(['status' => 'queued']);
    $fake = useResearcher([new ProposedTopic('Should not be used')]);

    $this->artisan('content:research-topics', ['--ensure' => 5])
        ->expectsOutputToContain('nothing to research')
        ->assertSuccessful();

    expect($fake->calledWithCount)->toBe(0)                 // never invoked
        ->and(Topic::where('title', 'Should not be used')->exists())->toBeFalse();
});

it('runs the command against the queue', function () {
    useResearcher([new ProposedTopic('Grip strength for pull-ups', 'Practical drills', 'grip strength')]);

    $this->artisan('content:research-topics', ['count' => 1])
        ->expectsOutputToContain('Added 1 topic(s)')
        ->assertSuccessful();

    expect(Topic::where('title', 'Grip strength for pull-ups')->exists())->toBeTrue();
});

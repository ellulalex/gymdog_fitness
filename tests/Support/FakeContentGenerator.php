<?php

namespace Tests\Support;

use App\Domain\Content\ContentGenerator;
use App\Domain\Content\GeneratedDraft;
use App\Models\Topic;

/**
 * Returns a canned draft so the pipeline can be tested without an API key.
 * Defaults to a clean, long-enough draft; pass a specific draft to exercise
 * the quality gates (banned claims, short body, duplicates).
 */
class FakeContentGenerator implements ContentGenerator
{
    public function __construct(public ?GeneratedDraft $draft = null) {}

    public function generate(Topic $topic, string $brandVoice): GeneratedDraft
    {
        return $this->draft ?? new GeneratedDraft(
            title: 'Guide to '.$topic->title,
            excerpt: 'A practical guide.',
            body: '<p>'.str_repeat('training tip about grips and ropes. ', 80).'</p>',
        );
    }
}

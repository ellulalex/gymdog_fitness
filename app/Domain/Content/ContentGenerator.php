<?php

namespace App\Domain\Content;

use App\Models\Topic;

/**
 * Seam for content generation. The pipeline depends on this, never on the
 * Anthropic SDK directly, so tests run without an API key (see the fake in
 * tests/Support) and the provider can be swapped.
 */
interface ContentGenerator
{
    public function generate(Topic $topic, string $brandVoice): GeneratedDraft;
}

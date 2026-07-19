<?php

namespace App\Domain\Content;

use App\Models\Post;

/**
 * Automated checks a draft must clear before it can be scheduled (spec §9).
 * Returns a list of human-readable violations — empty means it passed. The
 * banned-claims check is the load-bearing one: it's regulatory exposure for a
 * fitness site, not just quality control.
 */
class QualityGates
{
    /** @return string[] */
    public function check(GeneratedDraft $draft): array
    {
        $violations = [];

        if ($draft->wordCount() < config('content.min_words')) {
            $violations[] = 'Below the minimum word count.';
        }

        $haystack = strip_tags($draft->title.' '.$draft->body);
        foreach (config('content.banned_patterns') as $pattern) {
            if (preg_match($pattern, $haystack)) {
                $violations[] = 'Contains a medical/banned-claim phrase — needs human review.';
                break;
            }
        }

        // Duplicate detection: exact title, or a very similar existing title.
        if (Post::where('title', $draft->title)->exists()) {
            $violations[] = 'Duplicate of an existing post title.';
        } else {
            foreach (Post::pluck('title') as $existing) {
                similar_text(mb_strtolower($existing), mb_strtolower($draft->title), $percent);
                if ($percent > 90) {
                    $violations[] = 'Near-duplicate of an existing post title.';
                    break;
                }
            }
        }

        return $violations;
    }
}

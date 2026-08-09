<?php

namespace App\Domain\Content;

use App\Models\Post;
use App\Models\Topic;

/**
 * research → dedupe → enqueue. Fills the topic queue with fresh, search-driven
 * topics so the generator never runs dry, without ever queuing something we've
 * already written or already have waiting.
 */
class TopicResearchPipeline
{
    public function __construct(private readonly TopicResearcher $researcher) {}

    /**
     * @return array{added:int,skipped:int,titles:string[]}
     */
    public function run(int $count): array
    {
        $existing = $this->existingTitles();

        $proposals = $this->researcher->research($count, $existing, $this->coverage($existing));

        $position = (int) Topic::max('position');
        $added = [];
        $skipped = 0;

        foreach ($proposals as $topic) {
            if ($this->isDuplicate($topic->title, $existing)) {
                $skipped++;

                continue;
            }

            Topic::create([
                'title' => $topic->title,
                'angle' => $topic->angle ?: null,
                'focus_keyword' => $topic->focusKeyword ?: null,
                'status' => 'queued',
                'position' => ++$position,
            ]);

            $existing[] = $topic->title; // guard against dupes within the same batch
            $added[] = $topic->title;
        }

        return ['added' => count($added), 'skipped' => $skipped, 'titles' => $added];
    }

    /**
     * Roughly how many existing titles fall under each pillar, so the researcher
     * can steer towards the gaps. Keyword matching is deliberately crude — it
     * only needs to be good enough to reveal a lopsided library.
     *
     * @param  string[]  $titles
     * @return array<string,int>
     */
    private function coverage(array $titles): array
    {
        $pillars = (array) config('content.research.pillars', []);

        if ($pillars === [] || $titles === []) {
            return [];
        }

        $counts = [];
        foreach ($pillars as $pillar) {
            // Match on the pillar's significant words ("Hyrox racing and
            // stations" → hyrox, racing, stations).
            $words = array_filter(
                preg_split('/[^a-z]+/i', mb_strtolower($pillar)) ?: [],
                fn ($w) => mb_strlen($w) > 3 && ! in_array($w, ['and', 'the', 'with', 'from', 'getting', 'claims'], true),
            );

            $counts[$pillar] = 0;
            foreach ($titles as $title) {
                $haystack = mb_strtolower((string) $title);
                foreach ($words as $word) {
                    if (str_contains($haystack, $word)) {
                        $counts[$pillar]++;
                        break;
                    }
                }
            }
        }

        return $counts;
    }

    /** @return string[] */
    private function existingTitles(): array
    {
        return Topic::pluck('title')
            ->merge(Post::pluck('title'))
            ->filter()
            ->values()
            ->all();
    }

    /** @param  string[]  $existing */
    private function isDuplicate(string $title, array $existing): bool
    {
        $needle = mb_strtolower(trim($title));

        foreach ($existing as $candidate) {
            $hay = mb_strtolower(trim((string) $candidate));

            if ($hay === $needle) {
                return true;
            }

            similar_text($hay, $needle, $percent);
            if ($percent > 90) {
                return true;
            }
        }

        return false;
    }
}

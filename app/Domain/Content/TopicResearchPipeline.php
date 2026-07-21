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

        $proposals = $this->researcher->research($count, $existing);

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

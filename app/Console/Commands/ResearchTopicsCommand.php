<?php

namespace App\Console\Commands;

use App\Domain\Content\TopicResearchPipeline;
use App\Models\Topic;
use Illuminate\Console\Command;

class ResearchTopicsCommand extends Command
{
    protected $signature = 'content:research-topics
        {count? : How many topics to research}
        {--ensure= : Top the queue up to this many queued topics (skips the API call when already met)}';

    protected $description = 'Research fresh, search-driven topics and add the new ones to the queue.';

    public function handle(TopicResearchPipeline $pipeline): int
    {
        if (($ensure = $this->option('ensure')) !== null) {
            $queued = Topic::queued()->count();
            $count = max(0, (int) $ensure - $queued);

            if ($count === 0) {
                $this->info("Queue already holds {$queued} topic(s) — nothing to research.");

                return self::SUCCESS;
            }
        } else {
            $count = (int) ($this->argument('count') ?: config('content.research.default_count'));
        }

        $result = $pipeline->run($count);

        $this->info("Added {$result['added']} topic(s); skipped {$result['skipped']} duplicate(s).");
        foreach ($result['titles'] as $title) {
            $this->line("  • {$title}");
        }

        return self::SUCCESS;
    }
}

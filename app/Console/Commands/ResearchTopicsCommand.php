<?php

namespace App\Console\Commands;

use App\Domain\Content\TopicResearchPipeline;
use Illuminate\Console\Command;

class ResearchTopicsCommand extends Command
{
    protected $signature = 'content:research-topics {count? : How many topics to research}';

    protected $description = 'Research fresh, search-driven topics and add the new ones to the queue.';

    public function handle(TopicResearchPipeline $pipeline): int
    {
        $count = (int) ($this->argument('count') ?: config('content.research.default_count'));

        $result = $pipeline->run($count);

        $this->info("Added {$result['added']} topic(s); skipped {$result['skipped']} duplicate(s).");
        foreach ($result['titles'] as $title) {
            $this->line("  • {$title}");
        }

        return self::SUCCESS;
    }
}

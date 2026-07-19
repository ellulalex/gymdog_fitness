<?php

namespace App\Console\Commands;

use App\Domain\Content\GenerationPipeline;
use Illuminate\Console\Command;

class GenerateContentCommand extends Command
{
    protected $signature = 'content:generate';

    protected $description = 'Generate one post from the topic queue, through the quality gates.';

    public function handle(GenerationPipeline $pipeline): int
    {
        $post = $pipeline->run();

        if (! $post) {
            $this->info('No queued topics — nothing generated.');

            return self::SUCCESS;
        }

        $this->info("Generated “{$post->title}” [{$post->status}].");

        $violations = $post->generation_meta['violations'] ?? [];
        foreach ($violations as $violation) {
            $this->warn(" - {$violation}");
        }

        return self::SUCCESS;
    }
}

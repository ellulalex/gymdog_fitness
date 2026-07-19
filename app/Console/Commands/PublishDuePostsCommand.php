<?php

namespace App\Console\Commands;

use App\Models\Post;
use Illuminate\Console\Command;

class PublishDuePostsCommand extends Command
{
    protected $signature = 'content:publish-due';

    protected $description = 'Publish scheduled posts whose brake window has elapsed (unless rejected).';

    public function handle(): int
    {
        // Platform-wide job — run across all tenants, not just the resolved one.
        $count = Post::withoutTenantScope()
            ->where('status', 'scheduled')
            ->where('published_at', '<=', now())
            ->update(['status' => 'published']);

        $this->info("Published {$count} due post(s).");

        return self::SUCCESS;
    }
}

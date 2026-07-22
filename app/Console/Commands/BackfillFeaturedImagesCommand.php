<?php

namespace App\Console\Commands;

use App\Domain\Content\ImageFetcher;
use App\Models\Post;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class BackfillFeaturedImagesCommand extends Command
{
    protected $signature = 'content:backfill-images
        {--all : Include drafts and scheduled posts, not just published}';

    protected $description = 'Give posts without a featured image one: promote an inline body image, else fetch a stock photo.';

    public function handle(ImageFetcher $fetcher): int
    {
        $query = Post::whereNull('featured_image');
        if (! $this->option('all')) {
            $query->where('status', 'published');
        }

        $promoted = 0;
        $fetched = 0;
        $missed = 0;

        foreach ($query->get() as $post) {
            // 1. Reuse an image already in the article body.
            if (preg_match('/<img[^>]+src="([^"]+)"/i', (string) $post->body, $m)) {
                $post->update(['featured_image' => $m[1]]);
                $promoted++;
                $this->line("  ▪ promoted body image — {$post->title}");

                continue;
            }

            // 2. Fetch a relevant stock photo, falling back to a generic fitness
            // query when the specific one (a name, a rare movement) finds nothing.
            $image = $fetcher->fetch($post->focus_keyword ?: $this->queryFromTitle($post->title))
                ?? $fetcher->fetch('crossfit gym workout');
            if ($image) {
                $post->update(['featured_image' => $image->url, 'featured_image_credit' => $image->credit]);
                $fetched++;
                $this->line("  ▪ fetched stock photo — {$post->title}");

                continue;
            }

            $missed++;
            $this->warn("  ▪ no image found — {$post->title}");
        }

        $this->info("Promoted {$promoted}, fetched {$fetched}, missed {$missed}.");

        return self::SUCCESS;
    }

    /** A concise stock-search query from a title (drop years and filler). */
    private function queryFromTitle(string $title): string
    {
        $clean = Str::of($title)
            ->replaceMatches('/\b(19|20)\d{2}\b/', '')     // years
            ->replaceMatches('/[^\p{L}\s]/u', ' ')          // punctuation
            ->squish();

        $words = collect(explode(' ', (string) $clean))
            ->reject(fn ($w) => in_array(mb_strtolower($w), ['the', 'a', 'an', 'to', 'of', 'and', 'for', 'not', 'your', 'you', 'is', 'in']))
            ->take(4)
            ->implode(' ');

        return $words !== '' ? $words : 'crossfit gym';
    }
}

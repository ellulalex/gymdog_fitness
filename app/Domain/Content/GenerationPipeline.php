<?php

namespace App\Domain\Content;

use App\Mail\GeneratedPostReview;
use App\Models\Post;
use App\Models\Topic;
use App\Support\Tenancy\TenantManager;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * generate → quality-gate → schedule-with-a-brake (spec §9).
 *
 * A draft that clears the gates schedules for `content.brake_hours` ahead and
 * auto-publishes unless rejected (when auto-publish is on) — you only act to
 * stop it. A draft that fails, or when auto-publish is off, lands as a draft
 * for human review. Everything is tagged source=generated with full metadata.
 */
class GenerationPipeline
{
    public function __construct(
        private readonly ContentGenerator $generator,
        private readonly QualityGates $gates,
        private readonly ImageFetcher $images,
        private readonly InternalLinker $linker,
    ) {}

    public function run(): ?Post
    {
        $topic = Topic::queued()->first();

        if (! $topic) {
            return null;
        }

        $tenant = app(TenantManager::class)->current();
        $brandVoice = $tenant?->setting('brand_voice') ?? config('content.brand_voice');

        $draft = $this->generator->generate($topic, $brandVoice);
        $violations = $this->gates->check($draft);

        $passed = $violations === [];
        $autoPublish = config('content.auto_publish');
        $schedule = $passed && $autoPublish;

        // Post-process: link the first mention of existing posts, and fetch a
        // hero image. Both degrade gracefully — neither can block publishing.
        $linked = $this->linker->link($draft->body);
        $image = $this->images->fetch($draft->imageQuery ?: $draft->focusKeyword);

        $post = Post::create([
            'type' => 'post',
            'title' => $draft->title,
            'slug' => $this->uniqueSlug($draft->title),
            'excerpt' => $draft->excerpt,
            'body' => $linked['html'],
            'featured_image' => $image?->url,
            'featured_image_credit' => $image?->credit,
            'meta_title' => $draft->metaTitle ?: null,
            'meta_description' => $draft->metaDescription ?: null,
            'focus_keyword' => $draft->focusKeyword ?: null,
            'faq' => $draft->faq ?: null,
            'source' => 'generated',
            'status' => $schedule ? 'scheduled' : 'draft',
            'published_at' => $schedule ? now()->addHours(config('content.brake_hours')) : null,
            'generation_meta' => [
                'model' => config('content.model'),
                'topic_id' => $topic->id,
                'generated_at' => now()->toIso8601String(),
                'image_query' => $draft->imageQuery,
                'image_credit' => $image?->credit,
                'internal_links' => $linked['count'],
                'word_count' => $draft->wordCount(),
                'violations' => $violations,
            ],
        ]);

        $topic->markUsed();

        // Notify with a one-click reject link while the brake is on.
        if ($schedule && ($email = config('content.notify_email'))) {
            Mail::to($email)->send(new GeneratedPostReview($post));
        }

        return $post;
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'post';
        $slug = $base;
        $i = 2;

        while (Post::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}

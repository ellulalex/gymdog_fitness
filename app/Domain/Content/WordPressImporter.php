<?php

namespace App\Domain\Content;

use App\Models\Page;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Redirect;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Imports pages and posts from a live WordPress site's REST API into the
 * platform's content tables (Phase 3 migration, spec §10). Idempotent —
 * everything upserts on slug, so it's safe to re-run.
 *
 * URL patterns are preserved: WP page/post slugs become root-level /{slug}.
 * The eight CrossFit movement guides live as WP *pages* but import as
 * type=guide posts; a couple of slugs are dropped with a 301 instead.
 */
class WordPressImporter
{
    /** @var array<string,int> */
    public array $counts = [
        'pages' => 0, 'guides' => 0, 'posts' => 0,
        'categories' => 0, 'redirects' => 0, 'media' => 0, 'skipped' => 0,
    ];

    /** @var array<string,string> original image URL → local URL */
    private array $mediaMap = [];

    public function __construct(
        private readonly string $baseUrl,
        private readonly bool $rehostMedia = true,
    ) {}

    public function import(): array
    {
        $categoryMap = $this->importCategories();
        $this->importPages();
        $this->importPosts($categoryMap);
        $this->importRedirects();

        return $this->counts;
    }

    /** @return array<int,int> WordPress category id → local PostCategory id */
    private function importCategories(): array
    {
        $skip = config('content.wordpress.skip_categories');
        $map = [];

        foreach ($this->fetchAll('categories') as $cat) {
            if (in_array($cat['slug'], $skip, true)) {
                continue;
            }

            $model = PostCategory::updateOrCreate(
                ['slug' => $cat['slug']],
                ['name' => $this->plain($cat['name'] ?? $cat['slug'])],
            );

            $this->counts['categories'] += $model->wasRecentlyCreated ? 1 : 0;
            $map[$cat['id']] = $model->id;
        }

        return $map;
    }

    private function importPages(): void
    {
        $guides = config('content.wordpress.guide_slugs');
        $skip = config('content.wordpress.skip_slugs');

        foreach ($this->fetchAll('pages') as $item) {
            $slug = $item['slug'];

            if (in_array($slug, $skip, true)) {
                $this->counts['skipped']++;

                continue;
            }

            // Movement guides are WP pages but belong in the posts table.
            if (in_array($slug, $guides, true)) {
                $this->upsertPost($item, 'guide', []);
                $this->counts['guides']++;

                continue;
            }

            Page::updateOrCreate(
                ['slug' => $slug],
                [
                    'title' => $this->title($item),
                    'body' => $this->body($item),
                    'status' => $this->status($item),
                    'published_at' => $this->date($item),
                ],
            );
            $this->counts['pages']++;
        }
    }

    /** @param  array<int,int>  $categoryMap */
    private function importPosts(array $categoryMap): void
    {
        foreach ($this->fetchAll('posts') as $item) {
            $this->upsertPost($item, 'post', $categoryMap);
            $this->counts['posts']++;
        }
    }

    /** @param  array<int,int>  $categoryMap */
    private function upsertPost(array $item, string $type, array $categoryMap): Post
    {
        $post = Post::updateOrCreate(
            ['slug' => $item['slug']],
            [
                'type' => $type,
                'title' => $this->title($item),
                'excerpt' => Str::limit($this->plain($item['excerpt']['rendered'] ?? ''), 280, ''),
                'body' => $this->body($item),
                'status' => $this->status($item),
                'published_at' => $this->date($item),
                'source' => 'human',
            ],
        );

        $ourIds = array_values(array_filter(array_map(
            fn ($wpId) => $categoryMap[$wpId] ?? null,
            $item['categories'] ?? [],
        )));

        if ($ourIds) {
            $post->categories()->sync($ourIds);
        }

        return $post;
    }

    private function importRedirects(): void
    {
        foreach (config('content.wordpress.redirects') as $from => $to) {
            $redirect = Redirect::updateOrCreate(
                ['from_path' => $from],
                ['to_path' => $to, 'status_code' => 301],
            );
            $this->counts['redirects'] += $redirect->wasRecentlyCreated ? 1 : 0;
        }
    }

    // --- WordPress REST helpers ---

    /** @return array<int,array> */
    private function fetchAll(string $type): array
    {
        $items = [];
        $page = 1;

        do {
            $response = Http::acceptJson()->get(
                "{$this->baseUrl}/wp-json/wp/v2/{$type}",
                ['per_page' => 100, 'page' => $page],
            );

            // WP returns 400 once you page past the last page — a clean stop.
            if ($response->status() === 400) {
                break;
            }

            $response->throw();
            $batch = $response->json();

            if (! is_array($batch) || $batch === []) {
                break;
            }

            $items = array_merge($items, $batch);
            $total = (int) $response->header('X-WP-TotalPages') ?: 1;
            $page++;
        } while ($page <= $total);

        return $items;
    }

    private function title(array $item): string
    {
        return $this->plain($item['title']['rendered'] ?? $item['slug']);
    }

    private function status(array $item): string
    {
        return ($item['status'] ?? '') === 'publish' ? 'published' : 'draft';
    }

    private function date(array $item): ?Carbon
    {
        $value = $item['date_gmt'] ?? $item['date'] ?? null;

        return $value ? Carbon::parse($value) : null;
    }

    private function plain(string $value): string
    {
        return trim(html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5));
    }

    // --- Media re-hosting ---

    /** Body HTML with WordPress-hosted images downloaded and rewritten locally. */
    private function body(array $item): string
    {
        $html = $item['content']['rendered'] ?? '';

        if (! $this->rehostMedia || $html === '') {
            return $html;
        }

        // Drop responsive srcset/sizes so we only need to re-host the main src,
        // not every resized WordPress variant.
        $html = preg_replace('/\s+(?:srcset|sizes)="[^"]*"/i', '', $html) ?? $html;

        preg_match_all('/https?:\/\/[^\s"\'()]+?\.(?:jpe?g|png|gif|webp|svg)/i', $html, $matches);

        foreach (array_unique($matches[0]) as $url) {
            if (! $this->isWordPressMedia($url)) {
                continue;
            }

            if ($local = $this->download($url)) {
                $html = str_replace($url, $local, $html);
            }
        }

        return $html;
    }

    private function isWordPressMedia(string $url): bool
    {
        return str_contains($url, '/wp-content/')
            || parse_url($url, PHP_URL_HOST) === parse_url($this->baseUrl, PHP_URL_HOST);
    }

    /** Download an image to the public disk once; return its local URL (or null). */
    private function download(string $url): ?string
    {
        if (isset($this->mediaMap[$url])) {
            return $this->mediaMap[$url];
        }

        try {
            $response = Http::get($url);

            if (! $response->successful()) {
                return null;
            }

            $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION)) ?: 'jpg';
            $path = 'content-media/'.sha1($url).'.'.$ext;

            Storage::disk('public')->put($path, $response->body());

            $local = Storage::disk('public')->url($path);
            $this->mediaMap[$url] = $local;
            $this->counts['media']++;

            return $local;
        } catch (Throwable) {
            return null; // leave the original URL; import shouldn't fail on one image
        }
    }
}

<?php

namespace App\Domain\Content;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Fetches one relevant stock photo for a generated article from a free API
 * (Unsplash preferred, Pexels fallback) and re-hosts it on the public disk so
 * we never hot-link. Returns null — never throws — when no key is configured
 * or anything goes wrong: a missing hero image must never block publishing.
 */
class ImageFetcher
{
    public function fetch(?string $query): ?FetchedImage
    {
        $query = trim((string) $query);
        if ($query === '') {
            return null;
        }

        try {
            return match (config('content.images.provider')) {
                'pexels' => $this->pexels($query) ?? $this->unsplash($query),
                default => $this->unsplash($query) ?? $this->pexels($query),
            };
        } catch (Throwable) {
            return null;
        }
    }

    private function unsplash(string $query): ?FetchedImage
    {
        $key = config('services.unsplash.access_key');
        if (! $key) {
            return null;
        }

        $res = Http::withHeaders(['Authorization' => "Client-ID {$key}"])
            ->get('https://api.unsplash.com/search/photos', [
                'query' => $query,
                'per_page' => 1,
                'orientation' => config('content.images.orientation', 'landscape'),
                'content_filter' => 'high',
            ]);

        $photo = $res->successful() ? ($res->json('results.0') ?? null) : null;
        if (! is_array($photo) || empty($photo['urls']['regular'])) {
            return null;
        }

        // Unsplash API guideline: ping the download endpoint on use (best effort).
        if (! empty($photo['links']['download_location'])) {
            rescue(fn () => Http::withHeaders(['Authorization' => "Client-ID {$key}"])
                ->get($photo['links']['download_location']), report: false);
        }

        $name = $photo['user']['name'] ?? 'Unsplash';
        $local = $this->store((string) $photo['urls']['regular'], $query);

        return $local ? new FetchedImage($local, "Photo by {$name} on Unsplash") : null;
    }

    private function pexels(string $query): ?FetchedImage
    {
        $key = config('services.pexels.key');
        if (! $key) {
            return null;
        }

        $res = Http::withHeaders(['Authorization' => $key])
            ->get('https://api.pexels.com/v1/search', [
                'query' => $query,
                'per_page' => 1,
                'orientation' => config('content.images.orientation', 'landscape'),
            ]);

        $photo = $res->successful() ? ($res->json('photos.0') ?? null) : null;
        if (! is_array($photo) || empty($photo['src']['large'])) {
            return null;
        }

        $name = $photo['photographer'] ?? 'Pexels';
        $local = $this->store((string) $photo['src']['large'], $query);

        return $local ? new FetchedImage($local, "Photo by {$name} on Pexels") : null;
    }

    /** Download the remote image to the public disk; return its local URL. */
    private function store(string $url, string $query): ?string
    {
        $res = Http::get($url);
        if (! $res->successful()) {
            return null;
        }

        $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION)) ?: 'jpg';
        $path = 'content-media/'.sha1($query.'|'.$url).'.'.$ext;
        Storage::disk('public')->put($path, $res->body());

        return Storage::disk('public')->url($path);
    }
}

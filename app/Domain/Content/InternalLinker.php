<?php

namespace App\Domain\Content;

use App\Models\Post;
use DOMDocument;
use DOMText;
use DOMXPath;

/**
 * Links the first mention of an existing published post's focus keyword (or
 * title) in a draft's body to that post. Works on the parsed DOM — never a
 * naive string replace — so it can't corrupt tags, and it skips text that is
 * already inside a link or a heading. Capped by content.internal_links.max.
 */
class InternalLinker
{
    /**
     * @return array{html:string,count:int}
     */
    public function link(string $html, ?int $excludeId = null): array
    {
        if (! config('content.internal_links.enabled') || trim($html) === '') {
            return ['html' => $html, 'count' => 0];
        }

        $candidates = $this->candidates($excludeId);
        if ($candidates === []) {
            return ['html' => $html, 'count' => 0];
        }

        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML(
            '<?xml encoding="UTF-8"?><div id="__linkroot">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();

        $root = $dom->getElementsByTagName('div')->item(0);
        if (! $root) {
            return ['html' => $html, 'count' => 0];
        }

        $xpath = new DOMXPath($dom);
        $textQuery = '//text()[not(ancestor::a) and not(ancestor::h1) and not(ancestor::h2)'
            .' and not(ancestor::h3) and not(ancestor::h4) and not(ancestor::h5) and not(ancestor::h6)]';

        $max = (int) config('content.internal_links.max');
        $count = 0;

        foreach ($candidates as $slug => $phrase) {
            if ($count >= $max) {
                break;
            }

            $pattern = '/\b'.preg_quote($phrase, '/').'\b/iu';

            foreach ($xpath->query($textQuery) as $node) {
                if (! $node instanceof DOMText) {
                    continue;
                }
                if (preg_match($pattern, $node->nodeValue, $m, PREG_OFFSET_CAPTURE)) {
                    $this->wrap($dom, $node, (int) $m[0][1], $m[0][0], $slug);
                    $count++;
                    break;
                }
            }
        }

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $dom->saveHTML($child);
        }

        return ['html' => $out, 'count' => $count];
    }

    /** Split a text node around the match and insert an anchor in its place. */
    private function wrap(DOMDocument $dom, DOMText $node, int $offset, string $matched, string $slug): void
    {
        $value = $node->nodeValue;
        $before = substr($value, 0, $offset);
        $after = substr($value, $offset + strlen($matched));

        $anchor = $dom->createElement('a');
        $anchor->setAttribute('href', '/'.$slug);
        $anchor->appendChild($dom->createTextNode($matched));

        $parent = $node->parentNode;
        $parent->insertBefore($dom->createTextNode($before), $node);
        $parent->insertBefore($anchor, $node);
        $parent->insertBefore($dom->createTextNode($after), $node);
        $parent->removeChild($node);
    }

    /**
     * Published posts as slug => link-phrase, longest phrase first so more
     * specific keywords win over generic ones.
     *
     * @return array<string,string>
     */
    private function candidates(?int $excludeId): array
    {
        $posts = Post::published()
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->get(['slug', 'title', 'focus_keyword']);

        $map = [];
        foreach ($posts as $post) {
            $phrase = trim((string) ($post->focus_keyword ?: $post->title));
            if (mb_strlen($phrase) >= 4) {
                $map[$post->slug] = $phrase;
            }
        }

        uasort($map, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));

        return $map;
    }
}

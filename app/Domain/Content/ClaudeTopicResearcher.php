<?php

namespace App\Domain\Content;

use Anthropic\Client;
use RuntimeException;

/**
 * Uses Claude with web search to find current, search-worthy article topics for
 * the functional-fitness / Hyrox / CrossFit niche — trending events, common
 * questions, seasonal angles — and returns them with a target focus keyword.
 * Streamed for the same reason the article generator is: the web-search loop
 * runs server-side and can take a while.
 */
class ClaudeTopicResearcher implements TopicResearcher
{
    public function research(int $count, array $avoid = []): array
    {
        $client = new Client(apiKey: config('services.anthropic.key'));

        $niche = config('content.research.niche');

        $system = <<<SYS
        You are an SEO content strategist for GymDog, a CrossFit equipment shop
        in Malta. Propose blog article topics for this niche: {$niche}

        Use web search to ground ideas in what people are actually searching for
        and talking about right now — upcoming or recent Hyrox/CrossFit events,
        common beginner questions, gear comparisons, seasonal training angles.

        Each topic must: target a realistic search phrase (not impossibly
        competitive), be genuinely useful to athletes/coaches, suit a Maltese
        audience, and be distinct from the others. No medical or health-claim
        angles.

        Reply with ONLY a JSON array of exactly {$count} objects, no preamble:
        [{"title": string, "angle": string (one sentence on the take/structure),
          "focus_keyword": string (the search phrase to target)}]
        SYS;

        $prompt = "Propose {$count} topics.";
        if ($avoid !== []) {
            $prompt .= "\n\nDo NOT propose anything overlapping these existing titles:\n- ".implode("\n- ", $avoid);
        }

        $args = [
            'model' => config('content.model'),
            'maxTokens' => 3000,
            'system' => $system,
            'messages' => [['role' => 'user', 'content' => $prompt]],
        ];

        if (config('content.web_search')) {
            $args['tools'] = [[
                'type' => 'web_search_20260209',
                'name' => 'web_search',
                'max_uses' => (int) config('content.web_search_max_uses', 5),
            ]];
        }

        return $this->parse($this->stream($client->messages->createStream(...$args)));
    }

    private function stream(iterable $events): string
    {
        $text = '';
        foreach ($events as $event) {
            if (($event->type ?? null) === 'content_block_delta'
                && ($event->delta->type ?? null) === 'text_delta') {
                $text .= $event->delta->text;
            }
        }

        return $text;
    }

    /** @return ProposedTopic[] */
    private function parse(string $text): array
    {
        $start = strpos($text, '[');
        $end = strrpos($text, ']');

        if ($start === false || $end === false) {
            throw new RuntimeException('Topic researcher returned no JSON array.');
        }

        $data = json_decode(substr($text, $start, $end - $start + 1), true);

        if (! is_array($data)) {
            throw new RuntimeException('Topic researcher returned malformed JSON.');
        }

        $topics = [];
        foreach ($data as $item) {
            if (is_array($item) && ! empty($item['title'])) {
                $topics[] = new ProposedTopic(
                    title: (string) $item['title'],
                    angle: (string) ($item['angle'] ?? ''),
                    focusKeyword: (string) ($item['focus_keyword'] ?? ''),
                );
            }
        }

        return $topics;
    }
}

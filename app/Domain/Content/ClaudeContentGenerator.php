<?php

namespace App\Domain\Content;

use Anthropic\Client;
use App\Models\Topic;
use RuntimeException;

/**
 * Generates a long-form (~2000 word) post draft with Claude, grounded in a few
 * live web searches so event coverage and figures are current rather than
 * hallucinated. The prompt asks for strict JSON so the result parses
 * deterministically; the quality gates — not the prompt — are the real
 * guardrail against bad output.
 */
class ClaudeContentGenerator implements ContentGenerator
{
    public function generate(Topic $topic, string $brandVoice): GeneratedDraft
    {
        $client = new Client(apiKey: config('services.anthropic.key'));

        $target = (int) config('content.target_words', 2000);

        $system = <<<SYS
        You are an expert functional-fitness writer for GymDog, a CrossFit
        equipment shop in Malta. You write for athletes and coaches who train
        CrossFit, Hyrox and functional fitness, and who follow the competitive
        scene. Voice: {$brandVoice}

        Research the topic with web search first when it involves events,
        results, dates or anything time-sensitive — never invent standings,
        scores, dates or statistics. Prefer primary sources.

        Write an original article of about {$target} words. Structure it with
        <h2>/<h3> headings, short <p> paragraphs and <ul> lists where they help.
        Open with a hook, give genuinely useful specifics, and where natural tie
        back to training or equipment without being salesy. Write for a Maltese
        audience (metric units, local relevance) but keep it globally accurate.

        HARD RULES: no medical claims, no dosage/treatment/injury-recovery
        advice, no supplement health claims, no promises of health outcomes.

        Reply with ONLY a JSON object, no preamble, no markdown fence:
        {
          "title": string,
          "meta_title": string (<=60 chars, SEO title),
          "meta_description": string (<=155 chars),
          "focus_keyword": string (the primary search phrase this ranks for),
          "excerpt": string (<=160 chars, plain text),
          "body": string (HTML, <h2>/<h3>/<p>/<ul>/<ol>/<strong> only, no <h1>),
          "faq": [{"question": string, "answer": string}]  (3-5 items),
          "image_query": string (2-4 words to find a relevant stock photo)
        }
        SYS;

        $prompt = "Topic: {$topic->title}";
        if ($topic->angle) {
            $prompt .= "\nAngle to take: {$topic->angle}";
        }
        $prompt .= "\n\nResearch as needed, then return the JSON.";

        $args = [
            'model' => config('content.model'),
            'maxTokens' => (int) config('content.max_tokens'),
            'system' => $system,
            'messages' => [['role' => 'user', 'content' => $prompt]],
        ];

        if (config('content.web_search')) {
            $args['tools'] = [[
                'type' => 'web_search_20260209',
                'name' => 'web_search',
                'max_uses' => 5,
            ]];
        }

        $message = $client->messages->create(...$args);

        return $this->parse($this->text($message));
    }

    /** Concatenate the text content blocks of the response (skips tool blocks). */
    private function text(object $message): string
    {
        $text = '';
        foreach ($message->content as $block) {
            if (($block->type ?? null) === 'text') {
                $text .= $block->text;
            }
        }

        return $text;
    }

    private function parse(string $text): GeneratedDraft
    {
        $start = strpos($text, '{');
        $end = strrpos($text, '}');

        if ($start === false || $end === false) {
            throw new RuntimeException('Generator returned no JSON.');
        }

        $data = json_decode(substr($text, $start, $end - $start + 1), true);

        if (! is_array($data) || ! isset($data['title'], $data['body'])) {
            throw new RuntimeException('Generator returned malformed JSON.');
        }

        return new GeneratedDraft(
            title: (string) $data['title'],
            excerpt: (string) ($data['excerpt'] ?? ''),
            body: (string) $data['body'],
            metaTitle: (string) ($data['meta_title'] ?? ''),
            metaDescription: (string) ($data['meta_description'] ?? ''),
            focusKeyword: (string) ($data['focus_keyword'] ?? ''),
            faq: $this->cleanFaq($data['faq'] ?? []),
            imageQuery: isset($data['image_query']) ? (string) $data['image_query'] : null,
        );
    }

    /**
     * @param  mixed  $faq
     * @return array<int,array{question:string,answer:string}>
     */
    private function cleanFaq($faq): array
    {
        if (! is_array($faq)) {
            return [];
        }

        $clean = [];
        foreach ($faq as $item) {
            if (is_array($item) && isset($item['question'], $item['answer'])) {
                $clean[] = [
                    'question' => (string) $item['question'],
                    'answer' => (string) $item['answer'],
                ];
            }
        }

        return $clean;
    }
}

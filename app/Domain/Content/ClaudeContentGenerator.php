<?php

namespace App\Domain\Content;

use Anthropic\Client;
use App\Models\Topic;
use RuntimeException;

/**
 * Generates a post draft with Claude (model claude-opus-4-8 by default). The
 * prompt asks for strict JSON so the result parses deterministically; the
 * quality gates — not the prompt — are the real guardrail against bad output.
 */
class ClaudeContentGenerator implements ContentGenerator
{
    public function generate(Topic $topic, string $brandVoice): GeneratedDraft
    {
        $client = new Client(apiKey: config('services.anthropic.key'));

        $system = <<<'SYS'
        You are a fitness content writer. Write an original, accurate blog post.
        HARD RULES: no medical claims, no dosage or treatment or injury-recovery
        advice, no supplement health claims. Reply with ONLY a JSON object:
        {"title": string, "excerpt": string (<=160 chars), "body": string (HTML, <p>/<h2>/<ul> only)}.
        SYS;

        $prompt = "Brand voice: {$brandVoice}\n\nTopic: {$topic->title}";
        if ($topic->angle) {
            $prompt .= "\nAngle: {$topic->angle}";
        }

        $message = $client->messages->create(
            model: config('content.model'),
            maxTokens: config('content.max_tokens'),
            system: $system,
            messages: [['role' => 'user', 'content' => $prompt]],
        );

        return $this->parse($this->text($message));
    }

    /** Concatenate the text content blocks of the response. */
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
        );
    }
}

<?php

namespace App\Domain\Content;

final readonly class GeneratedDraft
{
    public function __construct(
        public string $title,
        public string $excerpt,
        public string $body,
    ) {}

    public function wordCount(): int
    {
        return str_word_count(strip_tags($this->body));
    }
}

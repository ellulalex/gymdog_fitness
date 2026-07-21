<?php

namespace App\Domain\Content;

final readonly class GeneratedDraft
{
    /**
     * @param  array<int,array{question:string,answer:string}>  $faq
     */
    public function __construct(
        public string $title,
        public string $excerpt,
        public string $body,
        public string $metaTitle = '',
        public string $metaDescription = '',
        public string $focusKeyword = '',
        public array $faq = [],
        public ?string $imageQuery = null,
    ) {}

    public function wordCount(): int
    {
        return str_word_count(strip_tags($this->body));
    }
}

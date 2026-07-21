<?php

namespace App\Domain\Content;

final readonly class ProposedTopic
{
    public function __construct(
        public string $title,
        public string $angle = '',
        public string $focusKeyword = '',
    ) {}
}

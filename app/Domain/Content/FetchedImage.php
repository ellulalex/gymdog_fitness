<?php

namespace App\Domain\Content;

final readonly class FetchedImage
{
    public function __construct(
        public string $url,      // local (public disk) URL
        public string $credit,   // human-readable attribution
    ) {}
}

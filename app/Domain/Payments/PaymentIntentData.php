<?php

namespace App\Domain\Payments;

final readonly class PaymentIntentData
{
    public function __construct(
        public string $id,
        public string $clientSecret,
    ) {}
}

<?php

namespace App\Application\Registration\ResendCode\DTO;

final class ResendCodeRequest
{
    public function __construct(
        public readonly string $requestId,
        public readonly ?string $urlPageCheckout,
        public readonly ?string $userAgent,
    ) {}
}

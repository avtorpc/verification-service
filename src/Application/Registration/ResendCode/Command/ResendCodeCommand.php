<?php

namespace App\Application\Registration\ResendCode\Command;

final class ResendCodeCommand
{
    public function __construct(
        public readonly string $requestId,
        public readonly ?string $urlPageCheckout,
        public readonly ?string $userAgent,
        public readonly ?string $ip,
    ) {}
}

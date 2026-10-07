<?php

namespace App\Application\Registration\ResendSmsCode\Command;

final class ResendSmsCodeCommand
{
    public function __construct(
        public readonly string $requestId,
        public readonly ?string $userAgent,
        public readonly ?string $ip,
    ) {}
}

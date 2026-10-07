<?php

namespace App\Application\Registration\ResendSmsCode\DTO;

final class ResendSmsCodeRequest
{
    public function __construct(
        public readonly string $requestId,
        public readonly ?string $userAgent,
    ) {}
}

<?php
namespace App\Application\Registration\Verification\Command;

final class VerificationCodeCommand
{
    public function __construct(
        public string $requestId,
        public string $channelCode,
        public string $verificationCode
    ) {}
}

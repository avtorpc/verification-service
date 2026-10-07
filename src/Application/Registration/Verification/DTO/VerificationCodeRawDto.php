<?php

namespace App\Application\Registration\Verification\DTO;

/**
 * @psalm-suppress PossiblyUnusedProperty
 */
final class VerificationCodeRawDto
{
    public mixed $requestId = null;
    public mixed $channelCode = null;
    public mixed $verificationCode = null;
}

<?php

namespace App\Application\Registration\Verification\DTO;

final class VerificationCodeRequest
{
    public string $requestId;
    public string $channelCode;
    public string $verificationCode;

    public function __construct(
        string $requestId,
        string $channelCode,
        string $verificationCode
    ) {
        $this->requestId = $requestId;
        $this->channelCode = $channelCode;
        $this->verificationCode = $verificationCode;
    }
}

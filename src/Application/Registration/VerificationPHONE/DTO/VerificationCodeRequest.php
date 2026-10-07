<?php

namespace App\Application\Registration\VerificationPHONE\DTO;

final class VerificationCodeRequest
{
    public function __construct(
        public string $requestId,
        public string $legalEntity,
        public string $countryAlpha2,
        public string $lastName,
        public string $email,
        public string $firstName,
        public ?string $patronymic,
        public string $phoneNumber,
        public string $channelCode,
    ) {}
}

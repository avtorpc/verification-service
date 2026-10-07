<?php
namespace App\Application\Registration\VerificationPHONE\Command;

final class VerificationCodeCommand
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

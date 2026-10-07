<?php

namespace App\Application\Registration\DTO;

class RegistrationCompleteRequest
{
    public function __construct(
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $email,
        public readonly string $phoneNumber,
        public readonly string $verificationChannelId,
        public readonly ?string $patronymic = null,
    ) {
    }
}

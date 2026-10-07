<?php

namespace App\Application\Registration\Mapper;

use App\Application\Registration\DTO\RegistrationCompleteRequest;

class RegistrationJsonMapper
{
    public function map(array $data): RegistrationCompleteRequest
    {
        return new RegistrationCompleteRequest(
            firstName: $data['firstName'] ?? '',
            lastName: $data['lastName'] ?? '',
            email: $data['email'] ?? '',
            phoneNumber: $data['phoneNumber'] ?? '',
            verificationChannelId: $data['verificationChannelId'] ?? 'sms',
            patronymic: $data['patronymic'] ?? null,
        );
    }
}

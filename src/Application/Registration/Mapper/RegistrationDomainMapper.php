<?php

namespace App\Application\Registration\Mapper;

use App\Application\Registration\DTO\RegistrationCompleteRequest;
use App\Shared\Exception\ValidationException;

class RegistrationDomainMapper
{
    public function map(RegistrationCompleteRequest $raw): RegistrationCompleteRequest
    {
        $errors = [];

        if (trim($raw->firstName) === '') {
            $errors[] = 'firstName is required';
        }

        if (trim($raw->lastName) === '') {
            $errors[] = 'lastName is required';
        }

        if (trim($raw->email) === '') {
            $errors[] = 'email is required';
        }

        if (trim($raw->phoneNumber) === '') {
            $errors[] = 'phoneNumber is required';
        }

        if (!in_array($raw->verificationChannelId, ['sms', 'email', 'telegram'], true)) {
            $errors[] = 'verificationChannelId must be one of: sms, email, telegram';
        }

        if (!empty($errors)) {
            throw new ValidationException(implode('; ', $errors));
        }

        return new RegistrationCompleteRequest(
            firstName: trim($raw->firstName),
            lastName: trim($raw->lastName),
            email: trim($raw->email),
            phoneNumber: trim($raw->phoneNumber),
            verificationChannelId: $raw->verificationChannelId,
            patronymic: $raw->patronymic !== null ? trim($raw->patronymic) : null,
        );
    }
}

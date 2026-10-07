<?php

namespace App\Application\CompanyContact\Mapper;

use App\Application\CompanyContact\DTO\ContactDomainRequest;
use App\Application\CompanyContact\DTO\ContactRawRequest;
use App\Shared\Exception\ValidationException;

class ContactDomainMapper
{
    private const ALLOWED_TYPES = ['phone', 'email', 'website', 'telegram', 'whatsapp'];

    public function map(ContactRawRequest $raw): ContactDomainRequest
    {
        $errors = [];

        if ($raw->companyId === null || !ctype_digit($raw->companyId)) {
            $errors[] = 'companyId is required and must be an integer';
        }

        if ($raw->contactType === null || !in_array($raw->contactType, self::ALLOWED_TYPES, true)) {
            $errors[] = 'contactType is required. Allowed: ' . implode(', ', self::ALLOWED_TYPES);
        }

        if ($raw->value === null || trim($raw->value) === '') {
            $errors[] = 'value is required';
        }

        if (!empty($errors)) {
            throw new ValidationException(implode('; ', $errors));
        }

        return new ContactDomainRequest(
            companyId: (int) $raw->companyId,
            contactType: $raw->contactType,
            value: trim($raw->value),
            isPrimary: $raw->isPrimary !== null ? filter_var($raw->isPrimary, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : null,
        );
    }
}

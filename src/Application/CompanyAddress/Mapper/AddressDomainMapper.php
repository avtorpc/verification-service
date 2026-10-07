<?php

namespace App\Application\CompanyAddress\Mapper;

use App\Application\CompanyAddress\DTO\AddressDomainRequest;
use App\Application\CompanyAddress\DTO\AddressRawRequest;
use App\Shared\Exception\ValidationException;

class AddressDomainMapper
{
    private const ALLOWED_TYPES = ['legal', 'postal'];

    public function map(AddressRawRequest $raw): AddressDomainRequest
    {
        $errors = [];

        if ($raw->companyId === null || !ctype_digit($raw->companyId)) {
            $errors[] = 'companyId is required and must be an integer';
        }

        if ($raw->addressType === null || !in_array($raw->addressType, self::ALLOWED_TYPES, true)) {
            $errors[] = 'addressType is required. Allowed: legal, postal';
        }

        if ($raw->countryCode === null || trim($raw->countryCode) === '') {
            $errors[] = 'countryCode is required';
        }

        if ($raw->region === null || trim($raw->region) === '') {
            $errors[] = 'region is required';
        }

        if ($raw->city === null || trim($raw->city) === '') {
            $errors[] = 'city is required';
        }

        if ($raw->street === null || trim($raw->street) === '') {
            $errors[] = 'street is required';
        }

        if ($raw->house === null || trim($raw->house) === '') {
            $errors[] = 'house is required';
        }

        if ($raw->zipCode === null || trim($raw->zipCode) === '') {
            $errors[] = 'zipCode is required';
        }

        if (!empty($errors)) {
            throw new ValidationException(implode('; ', $errors));
        }

        return new AddressDomainRequest(
            companyId: (int) $raw->companyId,
            addressType: $raw->addressType,
            countryCode: trim($raw->countryCode),
            region: trim($raw->region),
            city: trim($raw->city),
            street: trim($raw->street),
            house: trim($raw->house),
            zipCode: trim($raw->zipCode),
            apartment: $raw->apartment !== null ? trim($raw->apartment) : null,
            isSameAsLegal: $raw->isSameAsLegal !== null ? filter_var($raw->isSameAsLegal, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : null,
        );
    }
}

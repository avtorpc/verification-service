<?php

namespace App\Application\CompanyAddress\Mapper;

use App\Application\CompanyAddress\DTO\AddressRawRequest;

class AddressJsonMapper
{
    public function map(array $data): AddressRawRequest
    {
        return new AddressRawRequest(
            companyId: isset($data['companyId']) ? (string) $data['companyId'] : null,
            addressType: $data['addressType'] ?? null,
            countryCode: $data['countryCode'] ?? null,
            region: $data['region'] ?? null,
            city: $data['city'] ?? null,
            street: $data['street'] ?? null,
            house: $data['house'] ?? null,
            apartment: $data['apartment'] ?? null,
            zipCode: $data['zipCode'] ?? null,
            isSameAsLegal: isset($data['isSameAsLegal']) ? (string) $data['isSameAsLegal'] : null,
        );
    }
}

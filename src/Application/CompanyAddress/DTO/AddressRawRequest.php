<?php

namespace App\Application\CompanyAddress\DTO;

class AddressRawRequest
{
    public function __construct(
        public readonly ?string $companyId = null,
        public readonly ?string $addressType = null,
        public readonly ?string $countryCode = null,
        public readonly ?string $region = null,
        public readonly ?string $city = null,
        public readonly ?string $street = null,
        public readonly ?string $house = null,
        public readonly ?string $apartment = null,
        public readonly ?string $zipCode = null,
        public readonly ?string $isSameAsLegal = null,
    ) {
    }
}

<?php

namespace App\Application\CompanyAddress\Command;

class CreateAddressCommand
{
    public function __construct(
        public readonly int $companyId,
        public readonly string $addressType,
        public readonly string $countryCode,
        public readonly string $region,
        public readonly string $city,
        public readonly string $street,
        public readonly string $house,
        public readonly string $zipCode,
        public readonly ?string $apartment = null,
        public readonly ?bool $isSameAsLegal = null,
    ) {
    }
}

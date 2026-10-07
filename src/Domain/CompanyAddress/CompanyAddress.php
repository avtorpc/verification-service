<?php

namespace App\Domain\CompanyAddress;

class CompanyAddress
{
    public function __construct(
        public readonly int $id,
        public readonly int $companyId,
        public readonly string $addressType,
        public readonly string $countryCode,
        public readonly string $region,
        public readonly string $city,
        public readonly string $street,
        public readonly string $house,
        public readonly ?string $apartment,
        public readonly ?string $zipCode,
        public readonly ?bool $isSameAsLegal,
    ) {
    }

    public static function fromDatabaseRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            companyId: (int) $row['company_id'],
            addressType: $row['address_type'],
            countryCode: $row['country_code'],
            region: $row['region'],
            city: $row['city'],
            street: $row['street'],
            house: $row['house'],
            apartment: $row['apartment'],
            zipCode: $row['zip_code'],
            isSameAsLegal: $row['is_same_as_legal'] !== null ? (bool) $row['is_same_as_legal'] : null,
        );
    }
}

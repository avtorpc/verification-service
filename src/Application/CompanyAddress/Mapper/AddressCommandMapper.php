<?php

namespace App\Application\CompanyAddress\Mapper;

use App\Application\CompanyAddress\Command\CreateAddressCommand;
use App\Application\CompanyAddress\Command\UpdateAddressCommand;
use App\Application\CompanyAddress\DTO\AddressDomainRequest;

class AddressCommandMapper
{
    public function mapCreate(AddressDomainRequest $domain): CreateAddressCommand
    {
        return new CreateAddressCommand(
            companyId: $domain->companyId,
            addressType: $domain->addressType,
            countryCode: $domain->countryCode,
            region: $domain->region,
            city: $domain->city,
            street: $domain->street,
            house: $domain->house,
            zipCode: $domain->zipCode,
            apartment: $domain->apartment,
            isSameAsLegal: $domain->isSameAsLegal,
        );
    }

    public function mapUpdate(int $id, AddressDomainRequest $domain): UpdateAddressCommand
    {
        return new UpdateAddressCommand(
            id: $id,
            companyId: $domain->companyId,
            addressType: $domain->addressType,
            countryCode: $domain->countryCode,
            region: $domain->region,
            city: $domain->city,
            street: $domain->street,
            house: $domain->house,
            zipCode: $domain->zipCode,
            apartment: $domain->apartment,
            isSameAsLegal: $domain->isSameAsLegal,
        );
    }
}

<?php

namespace App\Application\CompanyContact\Mapper;

use App\Application\CompanyContact\Command\CreateContactCommand;
use App\Application\CompanyContact\Command\UpdateContactCommand;
use App\Application\CompanyContact\DTO\ContactDomainRequest;

class ContactCommandMapper
{
    public function mapCreate(ContactDomainRequest $domain): CreateContactCommand
    {
        return new CreateContactCommand(
            companyId: $domain->companyId,
            contactType: $domain->contactType,
            value: $domain->value,
            isPrimary: $domain->isPrimary,
        );
    }

    public function mapUpdate(int $id, ContactDomainRequest $domain): UpdateContactCommand
    {
        return new UpdateContactCommand(
            id: $id,
            companyId: $domain->companyId,
            contactType: $domain->contactType,
            value: $domain->value,
            isPrimary: $domain->isPrimary,
        );
    }
}

<?php

namespace App\Application\Company\Mapper;

use App\Application\Company\Command\CreateCompanyCommand;
use App\Application\Company\Command\UpdateCompanyCommand;
use App\Application\Company\DTO\CompanyDomainRequest;

class CompanyCommandMapper
{
    public function mapCreate(CompanyDomainRequest $domain): CreateCompanyCommand
    {
        return new CreateCompanyCommand(
            legalEntity: $domain->legalEntity,
            legalFormName: $domain->legalFormName,
            companyName: $domain->companyName,
            countryCode: $domain->countryCode,
            status: $domain->status,
            roleCode: $domain->roleCode,
            isKzNdsApplicable: $domain->isKzNdsApplicable,
            isNdsPayer: $domain->isNdsPayer,
        );
    }

    public function mapUpdate(int $id, CompanyDomainRequest $domain): UpdateCompanyCommand
    {
        return new UpdateCompanyCommand(
            id: $id,
            legalEntity: $domain->legalEntity,
            legalFormName: $domain->legalFormName,
            companyName: $domain->companyName,
            countryCode: $domain->countryCode,
            status: $domain->status,
            roleCode: $domain->roleCode,
            isKzNdsApplicable: $domain->isKzNdsApplicable,
            isNdsPayer: $domain->isNdsPayer,
        );
    }
}

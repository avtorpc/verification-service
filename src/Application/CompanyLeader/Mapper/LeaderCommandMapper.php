<?php

namespace App\Application\CompanyLeader\Mapper;

use App\Application\CompanyLeader\Command\CreateLeaderCommand;
use App\Application\CompanyLeader\Command\UpdateLeaderCommand;
use App\Application\CompanyLeader\DTO\LeaderDomainRequest;

class LeaderCommandMapper
{
    public function mapCreate(LeaderDomainRequest $domain): CreateLeaderCommand
    {
        return new CreateLeaderCommand(
            companyId: $domain->companyId,
            firstName: $domain->firstName,
            lastName: $domain->lastName,
            documentTypeCode: $domain->documentTypeCode,
            patronymic: $domain->patronymic,
        );
    }

    public function mapUpdate(int $id, LeaderDomainRequest $domain): UpdateLeaderCommand
    {
        return new UpdateLeaderCommand(
            id: $id,
            companyId: $domain->companyId,
            firstName: $domain->firstName,
            lastName: $domain->lastName,
            documentTypeCode: $domain->documentTypeCode,
            patronymic: $domain->patronymic,
        );
    }
}

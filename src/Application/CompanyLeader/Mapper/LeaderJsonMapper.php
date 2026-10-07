<?php

namespace App\Application\CompanyLeader\Mapper;

use App\Application\CompanyLeader\DTO\LeaderRawRequest;

class LeaderJsonMapper
{
    public function map(array $data): LeaderRawRequest
    {
        return new LeaderRawRequest(
            companyId: isset($data['companyId']) ? (string) $data['companyId'] : null,
            firstName: $data['firstName'] ?? null,
            lastName: $data['lastName'] ?? null,
            patronymic: $data['patronymic'] ?? null,
            documentTypeCode: $data['documentTypeCode'] ?? null,
        );
    }
}

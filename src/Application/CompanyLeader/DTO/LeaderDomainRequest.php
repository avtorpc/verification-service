<?php

namespace App\Application\CompanyLeader\DTO;

class LeaderDomainRequest
{
    public function __construct(
        public readonly int $companyId,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $documentTypeCode,
        public readonly ?string $patronymic = null,
    ) {
    }
}

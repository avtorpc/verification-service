<?php

namespace App\Application\CompanyLeader\DTO;

class LeaderRawRequest
{
    public function __construct(
        public readonly ?string $companyId = null,
        public readonly ?string $firstName = null,
        public readonly ?string $lastName = null,
        public readonly ?string $patronymic = null,
        public readonly ?string $documentTypeCode = null,
    ) {
    }
}

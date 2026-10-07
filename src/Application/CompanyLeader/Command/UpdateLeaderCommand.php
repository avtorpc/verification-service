<?php

namespace App\Application\CompanyLeader\Command;

class UpdateLeaderCommand
{
    public function __construct(
        public readonly int $id,
        public readonly int $companyId,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $documentTypeCode,
        public readonly ?string $patronymic = null,
    ) {
    }
}

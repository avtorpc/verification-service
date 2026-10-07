<?php

namespace App\Application\CompanyInfo\Command;

final class GetCompanyInfoCommand
{
    public function __construct(
        public readonly string $requestUserUuid,
        public readonly string $tokenUserUuid,
        public readonly int $companyId,
    ) {}
}

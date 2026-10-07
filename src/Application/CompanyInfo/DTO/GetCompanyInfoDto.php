<?php

namespace App\Application\CompanyInfo\DTO;

final class GetCompanyInfoDto
{
    public function __construct(
        public readonly string $userUuid,
        public readonly int $companyId,
    ) {}
}

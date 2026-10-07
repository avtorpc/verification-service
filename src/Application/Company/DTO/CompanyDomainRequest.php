<?php

namespace App\Application\Company\DTO;

class CompanyDomainRequest
{
    public function __construct(
        public readonly string $legalEntity,
        public readonly string $legalFormName,
        public readonly string $companyName,
        public readonly string $countryCode,
        public readonly string $status = 'draft',
        public readonly ?string $roleCode = null,
        public readonly ?bool $isKzNdsApplicable = null,
        public readonly ?bool $isNdsPayer = null,
    ) {
    }
}

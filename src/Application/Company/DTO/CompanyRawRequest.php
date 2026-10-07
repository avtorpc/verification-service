<?php

namespace App\Application\Company\DTO;

class CompanyRawRequest
{
    public function __construct(
        public readonly ?string $legalEntity = null,
        public readonly ?string $legalFormName = null,
        public readonly ?string $companyName = null,
        public readonly ?string $roleCode = null,
        public readonly ?string $countryCode = null,
        public readonly ?string $isKzNdsApplicable = null,
        public readonly ?string $isNdsPayer = null,
        public readonly ?string $status = null,
    ) {
    }
}

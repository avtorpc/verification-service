<?php

namespace App\Application\CompanyContact\DTO;

class ContactRawRequest
{
    public function __construct(
        public readonly ?string $companyId = null,
        public readonly ?string $contactType = null,
        public readonly ?string $value = null,
        public readonly ?string $isPrimary = null,
    ) {
    }
}

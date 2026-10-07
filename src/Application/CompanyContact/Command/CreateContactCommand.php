<?php

namespace App\Application\CompanyContact\Command;

class CreateContactCommand
{
    public function __construct(
        public readonly int $companyId,
        public readonly string $contactType,
        public readonly string $value,
        public readonly ?bool $isPrimary = null,
    ) {
    }
}

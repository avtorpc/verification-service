<?php

namespace App\Application\CompanyContact\Command;

class UpdateContactCommand
{
    public function __construct(
        public readonly int $id,
        public readonly int $companyId,
        public readonly string $contactType,
        public readonly string $value,
        public readonly ?bool $isPrimary = null,
    ) {
    }
}

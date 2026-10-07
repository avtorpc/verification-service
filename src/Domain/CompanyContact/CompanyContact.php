<?php

namespace App\Domain\CompanyContact;

class CompanyContact
{
    public function __construct(
        public readonly int $id,
        public readonly int $companyId,
        public readonly string $contactType,
        public readonly string $value,
        public readonly ?bool $isPrimary,
    ) {
    }

    public static function fromDatabaseRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            companyId: (int) $row['company_id'],
            contactType: $row['contact_type'],
            value: $row['value'],
            isPrimary: $row['is_primary'] !== null ? (bool) $row['is_primary'] : null,
        );
    }
}

<?php

namespace App\Domain\CompanyLeader;

class CompanyLeader
{
    public function __construct(
        public readonly int $id,
        public readonly int $companyId,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly ?string $patronymic,
        public readonly string $documentTypeCode,
    ) {
    }

    public static function fromDatabaseRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            companyId: (int) $row['company_id'],
            firstName: $row['first_name'],
            lastName: $row['last_name'],
            patronymic: $row['patronymic'],
            documentTypeCode: $row['document_type_code'],
        );
    }
}

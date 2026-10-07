<?php

namespace App\Domain\Company;

class Company
{
    public function __construct(
        public readonly int $id,
        public readonly string $legalEntity,
        public readonly string $legalFormName,
        public readonly string $companyName,
        public readonly string $countryCode,
        public readonly string $status,
        public readonly ?string $roleCode,
        public readonly ?bool $isKzNdsApplicable,
        public readonly ?bool $isNdsPayer,
        public readonly ?string $userUuid,
        public readonly \DateTimeImmutable $createdAt,
        public readonly \DateTimeImmutable $updatedAt,
    ) {
    }

    public static function fromDatabaseRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            legalEntity: $row['legal_entity'],
            legalFormName: $row['legal_form_name'],
            companyName: $row['company_name'],
            countryCode: $row['country_code'],
            status: $row['status'],
            roleCode: $row['role_code'],
            isKzNdsApplicable: $row['is_kz_nds_applicable'] !== null ? (bool) $row['is_kz_nds_applicable'] : null,
            isNdsPayer: $row['is_nds_payer'] !== null ? (bool) $row['is_nds_payer'] : null,
            userUuid: $row['user_uuid'],
            createdAt: self::toDateTime($row['created_at']),
            updatedAt: self::toDateTime($row['updated_at']),
        );
    }

    private static function toDateTime(mixed $value): \DateTimeImmutable
    {
        if ($value instanceof \DateTimeInterface) {
            return \DateTimeImmutable::createFromInterface($value);
        }
        return new \DateTimeImmutable($value);
    }
}

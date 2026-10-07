<?php

namespace App\Infrastructure\Persistence;

use App\Domain\CompanyContact\CompanyContact;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;

class DbalContactRepository
{
    private string $table;

    public function __construct(
        private Connection $connection,
        string $schema = 'verification',
    ) {
        $this->table = $schema . '.company_contacts';
    }

    public function insert(array $data): int
    {
        $sql = <<<SQL
            INSERT INTO {$this->table}
            (company_id, contact_type, value, is_primary, created_at, updated_at)
            VALUES
            (:company_id, :contact_type, :value, :is_primary, NOW(), NOW())
            RETURNING id
        SQL;

        return $this->connection->fetchOne($sql, [
            'company_id' => $data['company_id'],
            'contact_type' => $data['contact_type'],
            'value' => $data['value'],
            'is_primary' => $data['is_primary'] ?? null,
        ], [
            'company_id' => Types::INTEGER,
            'is_primary' => Types::BOOLEAN,
        ]);
    }

    public function update(int $id, array $data): void
    {
        $sql = <<<SQL
            UPDATE {$this->table}
            SET company_id = :company_id,
                contact_type = :contact_type,
                value = :value,
                is_primary = :is_primary,
                updated_at = NOW()
            WHERE id = :id
        SQL;

        $this->connection->executeStatement($sql, [
            'id' => $id,
            'company_id' => $data['company_id'],
            'contact_type' => $data['contact_type'],
            'value' => $data['value'],
            'is_primary' => $data['is_primary'] ?? null,
        ], [
            'id' => Types::INTEGER,
            'company_id' => Types::INTEGER,
            'is_primary' => Types::BOOLEAN,
        ]);
    }

    public function findById(int $id): ?CompanyContact
    {
        $result = $this->connection->fetchAssociative(
            "SELECT * FROM {$this->table} WHERE id = :id",
            ['id' => $id],
            ['id' => Types::INTEGER]
        );

        return $result ? CompanyContact::fromDatabaseRow($result) : null;
    }

    /** @return CompanyContact[] */
    public function findByCompanyId(int $companyId): array
    {
        $rows = $this->connection->fetchAllAssociative(
            "SELECT * FROM {$this->table} WHERE company_id = :company_id ORDER BY id",
            ['company_id' => $companyId],
            ['company_id' => Types::INTEGER]
        );

        return array_map([CompanyContact::class, 'fromDatabaseRow'], $rows);
    }

    public function deleteByCompanyId(int $companyId): void
    {
        $this->connection->executeStatement(
            "DELETE FROM {$this->table} WHERE company_id = :company_id",
            ['company_id' => $companyId],
            ['company_id' => Types::INTEGER]
        );
    }
}

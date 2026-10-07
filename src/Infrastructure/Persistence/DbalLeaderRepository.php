<?php

namespace App\Infrastructure\Persistence;

use App\Domain\CompanyLeader\CompanyLeader;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;

class DbalLeaderRepository
{
    private string $table;

    public function __construct(
        private Connection $connection,
        string $schema = 'verification',
    ) {
        $this->table = $schema . '.company_leaders';
    }

    public function insert(array $data): int
    {
        $sql = <<<SQL
            INSERT INTO {$this->table}
            (company_id, first_name, last_name, patronymic, document_type_code, created_at, updated_at)
            VALUES
            (:company_id, :first_name, :last_name, :patronymic, :document_type_code, NOW(), NOW())
            RETURNING id
        SQL;

        return $this->connection->fetchOne($sql, [
            'company_id' => $data['company_id'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'patronymic' => $data['patronymic'] ?? null,
            'document_type_code' => $data['document_type_code'],
        ], [
            'company_id' => Types::INTEGER,
        ]);
    }

    public function update(int $id, array $data): void
    {
        $sql = <<<SQL
            UPDATE {$this->table}
            SET company_id = :company_id,
                first_name = :first_name,
                last_name = :last_name,
                patronymic = :patronymic,
                document_type_code = :document_type_code,
                updated_at = NOW()
            WHERE id = :id
        SQL;

        $this->connection->executeStatement($sql, [
            'id' => $id,
            'company_id' => $data['company_id'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'patronymic' => $data['patronymic'] ?? null,
            'document_type_code' => $data['document_type_code'],
        ], [
            'id' => Types::INTEGER,
            'company_id' => Types::INTEGER,
        ]);
    }

    public function findById(int $id): ?CompanyLeader
    {
        $result = $this->connection->fetchAssociative(
            "SELECT * FROM {$this->table} WHERE id = :id",
            ['id' => $id],
            ['id' => Types::INTEGER]
        );

        return $result ? CompanyLeader::fromDatabaseRow($result) : null;
    }

    public function findByCompanyId(int $companyId): ?CompanyLeader
    {
        $result = $this->connection->fetchAssociative(
            "SELECT * FROM {$this->table} WHERE company_id = :company_id LIMIT 1",
            ['company_id' => $companyId],
            ['company_id' => Types::INTEGER]
        );

        return $result ? CompanyLeader::fromDatabaseRow($result) : null;
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

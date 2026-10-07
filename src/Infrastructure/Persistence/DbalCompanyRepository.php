<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Company\Company;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;

class DbalCompanyRepository
{
    private string $table;

    public function __construct(
        private Connection $connection,
        string $schema = 'verification',
    ) {
        $this->table = $schema . '.companies';
    }

    public function insert(array $data): int
    {
        $sql = <<<SQL
            INSERT INTO {$this->table}
            (legal_entity, legal_form_name, company_name, role_code, country_code, status, is_kz_nds_applicable, is_nds_payer, user_uuid, created_at, updated_at)
            VALUES
            (:legal_entity, :legal_form_name, :company_name, :role_code, :country_code, :status, :is_kz_nds_applicable, :is_nds_payer, :user_uuid, NOW(), NOW())
            RETURNING id
        SQL;

        return $this->connection->fetchOne($sql, [
            'legal_entity' => $data['legal_entity'],
            'legal_form_name' => $data['legal_form_name'],
            'company_name' => $data['company_name'],
            'role_code' => $data['role_code'],
            'country_code' => $data['country_code'],
            'status' => $data['status'],
            'is_kz_nds_applicable' => $data['is_kz_nds_applicable'],
            'is_nds_payer' => $data['is_nds_payer'],
            'user_uuid' => $data['user_uuid'] ?? null,
        ], [
            'is_kz_nds_applicable' => Types::BOOLEAN,
            'is_nds_payer' => Types::BOOLEAN,
        ]);
    }

    public function update(int $id, array $data): void
    {
        $sql = <<<SQL
            UPDATE {$this->table}
            SET legal_entity = :legal_entity,
                legal_form_name = :legal_form_name,
                company_name = :company_name,
                role_code = :role_code,
                country_code = :country_code,
                status = :status,
                is_kz_nds_applicable = :is_kz_nds_applicable,
                is_nds_payer = :is_nds_payer,
                updated_at = NOW()
            WHERE id = :id
        SQL;

        $this->connection->executeStatement($sql, [
            'id' => $id,
            'legal_entity' => $data['legal_entity'],
            'legal_form_name' => $data['legal_form_name'],
            'company_name' => $data['company_name'],
            'role_code' => $data['role_code'],
            'country_code' => $data['country_code'],
            'status' => $data['status'],
            'is_kz_nds_applicable' => $data['is_kz_nds_applicable'],
            'is_nds_payer' => $data['is_nds_payer'],
        ], [
            'id' => Types::INTEGER,
            'is_kz_nds_applicable' => Types::BOOLEAN,
            'is_nds_payer' => Types::BOOLEAN,
        ]);
    }

    public function findById(int $id): ?Company
    {
        $result = $this->connection->fetchAssociative(
            "SELECT * FROM {$this->table} WHERE id = :id",
            ['id' => $id],
            ['id' => Types::INTEGER]
        );

        return $result ? Company::fromDatabaseRow($result) : null;
    }

    public function existsByLegalEntity(string $legalEntity, ?int $excludeId = null): bool
    {
        $sql = "SELECT 1 FROM {$this->table} WHERE legal_entity = :legal_entity";
        $params = ['legal_entity' => $legalEntity];

        if ($excludeId !== null) {
            $sql .= ' AND id != :exclude_id';
            $params['exclude_id'] = $excludeId;
        }

        return (bool) $this->connection->fetchOne($sql, $params);
    }
}

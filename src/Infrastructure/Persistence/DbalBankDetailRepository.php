<?php

namespace App\Infrastructure\Persistence;

use App\Domain\CompanyBankDetail\CompanyBankDetail;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;

class DbalBankDetailRepository
{
    private string $table;

    public function __construct(
        private Connection $connection,
        string $schema = 'verification',
    ) {
        $this->table = $schema . '.company_bank_details';
    }

    public function insert(array $data): int
    {
        $sql = <<<SQL
            INSERT INTO {$this->table}
            (company_id, account_number, bank_name, bik, swift, correspondent_account, iban, country_code, created_at, updated_at)
            VALUES
            (:company_id, :account_number, :bank_name, :bik, :swift, :correspondent_account, :iban, :country_code, NOW(), NOW())
            RETURNING id
        SQL;

        return $this->connection->fetchOne($sql, [
            'company_id' => $data['company_id'],
            'account_number' => $data['account_number'],
            'bank_name' => $data['bank_name'],
            'bik' => $data['bik'],
            'swift' => $data['swift'] ?? '',
            'correspondent_account' => $data['correspondent_account'] ?? '',
            'iban' => $data['iban'] ?? '',
            'country_code' => $data['country_code'],
        ], [
            'company_id' => Types::INTEGER,
        ]);
    }

    public function update(int $id, array $data): void
    {
        $sql = <<<SQL
            UPDATE {$this->table}
            SET company_id = :company_id,
                account_number = :account_number,
                bank_name = :bank_name,
                bik = :bik,
                swift = :swift,
                correspondent_account = :correspondent_account,
                iban = :iban,
                country_code = :country_code,
                updated_at = NOW()
            WHERE id = :id
        SQL;

        $this->connection->executeStatement($sql, [
            'id' => $id,
            'company_id' => $data['company_id'],
            'account_number' => $data['account_number'],
            'bank_name' => $data['bank_name'],
            'bik' => $data['bik'],
            'swift' => $data['swift'] ?? '',
            'correspondent_account' => $data['correspondent_account'] ?? '',
            'iban' => $data['iban'] ?? '',
            'country_code' => $data['country_code'],
        ], [
            'id' => Types::INTEGER,
            'company_id' => Types::INTEGER,
        ]);
    }

    public function findById(int $id): ?CompanyBankDetail
    {
        $result = $this->connection->fetchAssociative(
            "SELECT * FROM {$this->table} WHERE id = :id",
            ['id' => $id],
            ['id' => Types::INTEGER]
        );

        return $result ? CompanyBankDetail::fromDatabaseRow($result) : null;
    }

    public function findByCompanyId(int $companyId): ?CompanyBankDetail
    {
        $result = $this->connection->fetchAssociative(
            "SELECT * FROM {$this->table} WHERE company_id = :company_id LIMIT 1",
            ['company_id' => $companyId],
            ['company_id' => Types::INTEGER]
        );

        return $result ? CompanyBankDetail::fromDatabaseRow($result) : null;
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

<?php

namespace App\Infrastructure\Persistence;

use App\Domain\CompanyAddress\CompanyAddress;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;

class DbalAddressRepository
{
    private string $table;

    public function __construct(
        private Connection $connection,
        string $schema = 'verification',
    ) {
        $this->table = $schema . '.company_addresses';
    }

    public function insert(array $data): int
    {
        $sql = <<<SQL
            INSERT INTO {$this->table}
            (company_id, address_type, country_code, region, city, street, house, apartment, zip_code, is_same_as_legal, created_at, updated_at)
            VALUES
            (:company_id, :address_type, :country_code, :region, :city, :street, :house, :apartment, :zip_code, :is_same_as_legal, NOW(), NOW())
            RETURNING id
        SQL;

        return $this->connection->fetchOne($sql, [
            'company_id' => $data['company_id'],
            'address_type' => $data['address_type'],
            'country_code' => $data['country_code'],
            'region' => $data['region'],
            'city' => $data['city'],
            'street' => $data['street'],
            'house' => $data['house'],
            'apartment' => $data['apartment'] ?? null,
            'zip_code' => $data['zip_code'],
            'is_same_as_legal' => $data['is_same_as_legal'] ?? null,
        ], [
            'company_id' => Types::INTEGER,
            'is_same_as_legal' => Types::BOOLEAN,
        ]);
    }

    public function update(int $id, array $data): void
    {
        $sql = <<<SQL
            UPDATE {$this->table}
            SET company_id = :company_id,
                address_type = :address_type,
                country_code = :country_code,
                region = :region,
                city = :city,
                street = :street,
                house = :house,
                apartment = :apartment,
                zip_code = :zip_code,
                is_same_as_legal = :is_same_as_legal,
                updated_at = NOW()
            WHERE id = :id
        SQL;

        $this->connection->executeStatement($sql, [
            'id' => $id,
            'company_id' => $data['company_id'],
            'address_type' => $data['address_type'],
            'country_code' => $data['country_code'],
            'region' => $data['region'],
            'city' => $data['city'],
            'street' => $data['street'],
            'house' => $data['house'],
            'apartment' => $data['apartment'] ?? null,
            'zip_code' => $data['zip_code'],
            'is_same_as_legal' => $data['is_same_as_legal'] ?? null,
        ], [
            'id' => Types::INTEGER,
            'company_id' => Types::INTEGER,
            'is_same_as_legal' => Types::BOOLEAN,
        ]);
    }

    public function findById(int $id): ?CompanyAddress
    {
        $result = $this->connection->fetchAssociative(
            "SELECT * FROM {$this->table} WHERE id = :id",
            ['id' => $id],
            ['id' => Types::INTEGER]
        );

        return $result ? CompanyAddress::fromDatabaseRow($result) : null;
    }

    /** @return CompanyAddress[] */
    public function findByCompanyId(int $companyId): array
    {
        $rows = $this->connection->fetchAllAssociative(
            "SELECT * FROM {$this->table} WHERE company_id = :company_id ORDER BY id",
            ['company_id' => $companyId],
            ['company_id' => Types::INTEGER]
        );

        return array_map([CompanyAddress::class, 'fromDatabaseRow'], $rows);
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

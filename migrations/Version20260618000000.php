<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260618000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create company tables (companies, addresses, leaders, contacts, bank_details)';
    }

    public function up(Schema $schema): void
    {
        $schemaName = $_ENV['DB_SCHEMA'] ?? 'public';

        $this->addSql("
            CREATE TABLE {$schemaName}.companies (
                id BIGSERIAL PRIMARY KEY,
                legal_entity VARCHAR(50) NOT NULL,
                legal_form_name VARCHAR(255) NOT NULL,
                company_name VARCHAR(255) NOT NULL,
                role_code VARCHAR(50) DEFAULT NULL,
                country_code VARCHAR(10) NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'draft',
                is_kz_nds_applicable BOOLEAN DEFAULT NULL,
                is_nds_payer BOOLEAN DEFAULT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL
            )
        ");

        $this->addSql("ALTER TABLE {$schemaName}.companies ADD CONSTRAINT uq_company_legal_entity UNIQUE (legal_entity)");
        $this->addSql("CREATE INDEX idx_company_status ON {$schemaName}.companies (status)");

        $this->addSql("
            CREATE TABLE {$schemaName}.company_addresses (
                id BIGSERIAL PRIMARY KEY,
                company_id BIGINT NOT NULL,
                address_type VARCHAR(10) NOT NULL,
                country_code VARCHAR(10) NOT NULL,
                region VARCHAR(100) NOT NULL,
                city VARCHAR(100) NOT NULL,
                street VARCHAR(255) NOT NULL,
                house VARCHAR(20) NOT NULL,
                apartment VARCHAR(20) DEFAULT NULL,
                zip_code VARCHAR(20) NOT NULL,
                is_same_as_legal BOOLEAN DEFAULT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL
            )
        ");

        $this->addSql("CREATE INDEX idx_address_company_id ON {$schemaName}.company_addresses (company_id)");
        $this->addSql("CREATE INDEX idx_address_type ON {$schemaName}.company_addresses (address_type)");

        $this->addSql("
            CREATE TABLE {$schemaName}.company_leaders (
                id BIGSERIAL PRIMARY KEY,
                company_id BIGINT NOT NULL,
                first_name VARCHAR(100) NOT NULL,
                last_name VARCHAR(100) NOT NULL,
                patronymic VARCHAR(100) DEFAULT NULL,
                document_type_code VARCHAR(50) NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL
            )
        ");

        $this->addSql("CREATE INDEX idx_leader_company_id ON {$schemaName}.company_leaders (company_id)");

        $this->addSql("
            CREATE TABLE {$schemaName}.company_contacts (
                id BIGSERIAL PRIMARY KEY,
                company_id BIGINT NOT NULL,
                contact_type VARCHAR(20) NOT NULL,
                value VARCHAR(255) NOT NULL,
                is_primary BOOLEAN DEFAULT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL
            )
        ");

        $this->addSql("CREATE INDEX idx_contact_company_id ON {$schemaName}.company_contacts (company_id)");
        $this->addSql("CREATE INDEX idx_contact_type ON {$schemaName}.company_contacts (contact_type)");

        $this->addSql("
            CREATE TABLE {$schemaName}.company_bank_details (
                id BIGSERIAL PRIMARY KEY,
                company_id BIGINT NOT NULL,
                account_number VARCHAR(50) NOT NULL,
                bank_name VARCHAR(255) NOT NULL,
                bik VARCHAR(9) NOT NULL,
                swift VARCHAR(11) NOT NULL,
                correspondent_account VARCHAR(50) NOT NULL,
                iban VARCHAR(34) NOT NULL,
                country_code VARCHAR(10) NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL
            )
        ");

        $this->addSql("CREATE INDEX idx_bank_detail_company_id ON {$schemaName}.company_bank_details (company_id)");
    }

    public function down(Schema $schema): void
    {
        $schemaName = $_ENV['DB_SCHEMA'] ?? 'public';

        $this->addSql("DROP TABLE IF EXISTS {$schemaName}.company_bank_details");
        $this->addSql("DROP TABLE IF EXISTS {$schemaName}.company_contacts");
        $this->addSql("DROP TABLE IF EXISTS {$schemaName}.company_leaders");
        $this->addSql("DROP TABLE IF EXISTS {$schemaName}.company_addresses");
        $this->addSql("DROP TABLE IF EXISTS {$schemaName}.companies");
    }
}

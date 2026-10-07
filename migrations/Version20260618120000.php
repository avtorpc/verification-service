<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260618120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create registration_progress table for company registration steps';
    }

    public function up(Schema $schema): void
    {
        $schemaName = $_ENV['DB_SCHEMA'] ?? 'public';

        $this->addSql("
            CREATE TABLE {$schemaName}.registration_progress (
                id BIGSERIAL PRIMARY KEY,
                company_id BIGINT NOT NULL REFERENCES {$schemaName}.companies(id),
                current_step VARCHAR(32) NOT NULL DEFAULT 'company_info',
                completed_steps JSONB NOT NULL DEFAULT '[]',
                status VARCHAR(16) NOT NULL DEFAULT 'in_progress',
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL
            )
        ");

        $this->addSql("CREATE UNIQUE INDEX idx_registration_progress_company ON {$schemaName}.registration_progress (company_id)");
    }

    public function down(Schema $schema): void
    {
        $schemaName = $_ENV['DB_SCHEMA'] ?? 'public';

        $this->addSql("DROP TABLE IF EXISTS {$schemaName}.registration_progress");
    }
}

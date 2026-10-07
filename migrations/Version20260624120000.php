<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260624120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add trace_id column to registration_progress';
    }

    public function up(Schema $schema): void
    {
        $schemaName = $_ENV['DB_SCHEMA'] ?? 'public';

        $this->addSql("
            ALTER TABLE {$schemaName}.registration_progress
            ADD COLUMN trace_id VARCHAR(36) DEFAULT NULL
        ");

        $this->addSql("
            COMMENT ON COLUMN {$schemaName}.registration_progress.trace_id IS 'Сквозной UUID для отслеживания запроса (trace_id)'
        ");
    }

    public function down(Schema $schema): void
    {
        $schemaName = $_ENV['DB_SCHEMA'] ?? 'public';

        $this->addSql("
            ALTER TABLE {$schemaName}.registration_progress
            DROP COLUMN trace_id
        ");
    }
}

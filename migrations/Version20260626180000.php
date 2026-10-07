<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260626180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add user_uuid column to companies table';
    }

    public function up(Schema $schema): void
    {
        $schemaName = $_ENV['DB_SCHEMA'] ?? 'public';

        $tableName = "\"{$schemaName}\".\"companies\"";

        $this->addSql("ALTER TABLE {$tableName} ADD COLUMN user_uuid VARCHAR(36) DEFAULT NULL");
    }

    public function down(Schema $schema): void
    {
        $schemaName = $_ENV['DB_SCHEMA'] ?? 'public';

        $tableName = "\"{$schemaName}\".\"companies\"";

        $this->addSql("ALTER TABLE {$tableName} DROP COLUMN user_uuid");
    }
}

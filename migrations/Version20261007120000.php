<?php

declare(strict_types=1);
namespace DoctrineMigrations;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261007120000 extends AbstractMigration
{
    public function getDescription(): string { return 'Immutable email signup deadline, independent of delivery retries'; }
    public function up(Schema $schema): void
    {
        $name = $_ENV['DB_SCHEMA'] ?? 'public';
        $table = '"'.str_replace('"', '""', $name).'"."signup_requests"';
        $this->addSql("ALTER TABLE {$table} ADD expires_at TIMESTAMPTZ");
        $this->addSql("UPDATE {$table} SET expires_at = created_at + INTERVAL '600 seconds'");
        $this->addSql("ALTER TABLE {$table} ALTER expires_at SET NOT NULL, ALTER expires_at SET DEFAULT (CURRENT_TIMESTAMP + INTERVAL '600 seconds')");
    }
    public function down(Schema $schema): void
    {
        $name = $_ENV['DB_SCHEMA'] ?? 'public';
        $table = '"'.str_replace('"', '""', $name).'"."signup_requests"';
        $this->addSql("ALTER TABLE {$table} DROP expires_at");
    }
}

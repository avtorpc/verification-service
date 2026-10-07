<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260624180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add FK constraints to child company tables with ON DELETE CASCADE';
    }

    public function up(Schema $schema): void
    {
        $schemaName = $_ENV['DB_SCHEMA'] ?? 'public';

        $this->addSql("
            ALTER TABLE {$schemaName}.company_addresses
            ADD CONSTRAINT fk_address_company
            FOREIGN KEY (company_id)
            REFERENCES {$schemaName}.companies(id)
            ON DELETE CASCADE
        ");

        $this->addSql("
            ALTER TABLE {$schemaName}.company_leaders
            ADD CONSTRAINT fk_leader_company
            FOREIGN KEY (company_id)
            REFERENCES {$schemaName}.companies(id)
            ON DELETE CASCADE
        ");

        $this->addSql("
            ALTER TABLE {$schemaName}.company_contacts
            ADD CONSTRAINT fk_contact_company
            FOREIGN KEY (company_id)
            REFERENCES {$schemaName}.companies(id)
            ON DELETE CASCADE
        ");

        $this->addSql("
            ALTER TABLE {$schemaName}.company_bank_details
            ADD CONSTRAINT fk_bank_detail_company
            FOREIGN KEY (company_id)
            REFERENCES {$schemaName}.companies(id)
            ON DELETE CASCADE
        ");
    }

    public function down(Schema $schema): void
    {
        $schemaName = $_ENV['DB_SCHEMA'] ?? 'public';

        $this->addSql("ALTER TABLE {$schemaName}.company_addresses DROP CONSTRAINT IF EXISTS fk_address_company");
        $this->addSql("ALTER TABLE {$schemaName}.company_leaders DROP CONSTRAINT IF EXISTS fk_leader_company");
        $this->addSql("ALTER TABLE {$schemaName}.company_contacts DROP CONSTRAINT IF EXISTS fk_contact_company");
        $this->addSql("ALTER TABLE {$schemaName}.company_bank_details DROP CONSTRAINT IF EXISTS fk_bank_detail_company");
    }
}

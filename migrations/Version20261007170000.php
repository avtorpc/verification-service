<?php
declare(strict_types=1);
namespace DoctrineMigrations;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
final class Version20261007170000 extends AbstractMigration
{
 public function getDescription(): string { return 'Email registration profiles, atomic OTP and durable delivery outbox'; }
 public function up(Schema $schema): void {
  $s = '"'.str_replace('"', '""', $_ENV['DB_SCHEMA'] ?? 'public').'"';
  $this->addSql("ALTER TABLE {$s}.signup_requests ADD profile JSONB NOT NULL DEFAULT '{}', ADD password_hash VARCHAR(255), ADD code_hash VARCHAR(64), ADD code_expires_at TIMESTAMPTZ, ADD cancelled_at TIMESTAMPTZ, ADD confirmed_at TIMESTAMPTZ, ADD completed_at TIMESTAMPTZ");
  $this->addSql("CREATE INDEX signup_email_window ON {$s}.signup_requests (lower(email), created_at)");
  $this->addSql("CREATE INDEX signup_ip_window ON {$s}.signup_requests (ip_address, created_at)");
  $this->addSql("CREATE TABLE {$s}.registration_outbox (event_id UUID PRIMARY KEY, request_id UUID NOT NULL, kind VARCHAR(20) NOT NULL CHECK(kind IN ('email','account')), generation INTEGER NOT NULL, payload JSONB NOT NULL, state VARCHAR(20) NOT NULL DEFAULT 'pending', attempts INTEGER NOT NULL DEFAULT 0, next_attempt_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP, created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE(request_id,kind,generation))");
  $this->addSql("CREATE INDEX registration_outbox_pending ON {$s}.registration_outbox (next_attempt_at) WHERE state = 'pending'");
 }
 public function down(Schema $schema): void { $this->throwIrreversibleMigrationException('Contains durable registration state.'); }
}

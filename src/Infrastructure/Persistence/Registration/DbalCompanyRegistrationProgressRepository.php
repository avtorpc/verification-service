<?php

namespace App\Infrastructure\Persistence\Registration;

use App\Domain\Registration\CompanyRegistrationProgress;
use App\Domain\Registration\Step\RegistrationStep;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;

class DbalCompanyRegistrationProgressRepository
{
    private string $table;

    public function __construct(
        private Connection $connection,
        string $schema = 'verification',
    ) {
        $this->table = $schema . '.registration_progress';
    }

    public function insert(int $companyId, ?string $traceId = null): int
    {
        $sql = <<<SQL
            INSERT INTO {$this->table}
            (company_id, current_step, completed_steps, status, trace_id, created_at, updated_at)
            VALUES
            (:company_id, :current_step, :completed_steps, :status, :trace_id, NOW(), NOW())
            RETURNING id
        SQL;

        return $this->connection->fetchOne($sql, [
            'company_id' => $companyId,
            'current_step' => RegistrationStep::COMPANY_INFO->value,
            'completed_steps' => '[]',
            'status' => 'in_progress',
            'trace_id' => $traceId,
        ], [
            'company_id' => Types::INTEGER,
            'trace_id' => Types::STRING,
        ]);
    }

    public function findByCompanyId(int $companyId): ?CompanyRegistrationProgress
    {
        $result = $this->connection->fetchAssociative(
            "SELECT * FROM {$this->table} WHERE company_id = :company_id",
            ['company_id' => $companyId],
            ['company_id' => Types::INTEGER]
        );

        return $result ? CompanyRegistrationProgress::fromDatabaseRow($result) : null;
    }

    public function updateStep(int $id, RegistrationStep $currentStep, array $completedSteps): void
    {
        $this->connection->executeStatement(
            "UPDATE {$this->table} SET current_step = :current_step, completed_steps = :completed_steps, updated_at = NOW() WHERE id = :id",
            [
                'id' => $id,
                'current_step' => $currentStep->value,
                'completed_steps' => json_encode(array_map(fn(RegistrationStep $s) => $s->value, $completedSteps)),
            ],
            [
                'id' => Types::INTEGER,
            ]
        );
    }

    public function markCompleted(int $id): void
    {
        $this->connection->executeStatement(
            "UPDATE {$this->table} SET status = 'completed', updated_at = NOW() WHERE id = :id",
            ['id' => $id],
            ['id' => Types::INTEGER]
        );
    }
}

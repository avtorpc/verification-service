<?php

namespace App\Domain\Registration;

use App\Domain\Registration\Step\RegistrationStep;

readonly class CompanyRegistrationProgress
{
    public function __construct(
        public int $id,
        public int $companyId,
        public RegistrationStep $currentStep,
        public array $completedSteps,
        public string $status,
        public ?string $traceId,
        public \DateTimeImmutable $createdAt,
        public \DateTimeImmutable $updatedAt,
    ) {
    }

    public static function fromDatabaseRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            companyId: (int) $row['company_id'],
            currentStep: RegistrationStep::from($row['current_step']),
            completedSteps: array_map(
                fn(string $s) => RegistrationStep::from($s),
                json_decode($row['completed_steps'] ?? '[]', true)
            ),
            status: $row['status'],
            traceId: $row['trace_id'] ?? null,
            createdAt: self::toDateTime($row['created_at']),
            updatedAt: self::toDateTime($row['updated_at']),
        );
    }

    private static function toDateTime(mixed $value): \DateTimeImmutable
    {
        if ($value instanceof \DateTimeInterface) {
            return \DateTimeImmutable::createFromInterface($value);
        }
        return new \DateTimeImmutable($value);
    }
}

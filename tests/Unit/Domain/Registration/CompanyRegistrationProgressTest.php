<?php

namespace App\Tests\Unit\Domain\Registration;

use App\Domain\Registration\CompanyRegistrationProgress;
use App\Domain\Registration\Step\RegistrationStep;
use PHPUnit\Framework\TestCase;

class CompanyRegistrationProgressTest extends TestCase
{
    public function testFromDatabaseRow(): void
    {
        $progress = CompanyRegistrationProgress::fromDatabaseRow([
            'id' => 1,
            'company_id' => 42,
            'current_step' => 'address',
            'completed_steps' => '["company_info"]',
            'status' => 'in_progress',
            'created_at' => '2026-06-18T12:00:00Z',
            'updated_at' => '2026-06-18T12:00:00Z',
        ]);

        self::assertSame(1, $progress->id);
        self::assertSame(42, $progress->companyId);
        self::assertSame(RegistrationStep::ADDRESS, $progress->currentStep);
        self::assertCount(1, $progress->completedSteps);
        self::assertSame(RegistrationStep::COMPANY_INFO, $progress->completedSteps[0]);
        self::assertSame('in_progress', $progress->status);
        self::assertInstanceOf(\DateTimeImmutable::class, $progress->createdAt);
    }

    public function testFromDatabaseRowWithEmptySteps(): void
    {
        $progress = CompanyRegistrationProgress::fromDatabaseRow([
            'id' => 2,
            'company_id' => 43,
            'current_step' => 'company_info',
            'completed_steps' => '[]',
            'status' => 'in_progress',
            'created_at' => '2026-06-18T12:00:00Z',
            'updated_at' => '2026-06-18T12:00:00Z',
        ]);

        self::assertEmpty($progress->completedSteps);
    }
}

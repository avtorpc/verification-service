<?php

namespace App\Tests\Unit\Infrastructure\Persistence\Registration;

use App\Domain\Registration\CompanyRegistrationProgress;
use App\Domain\Registration\Step\RegistrationStep;
use App\Infrastructure\Persistence\Registration\DbalCompanyRegistrationProgressRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use PHPUnit\Framework\TestCase;

class DbalCompanyRegistrationProgressRepositoryTest extends TestCase
{
    private Connection $connection;
    private DbalCompanyRegistrationProgressRepository $repository;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->repository = new DbalCompanyRegistrationProgressRepository($this->connection, 'verification');
    }

    public function testInsertReturnsId(): void
    {
        $this->connection
            ->expects(self::once())
            ->method('fetchOne')
            ->willReturn('5');

        $id = $this->repository->insert(42);

        self::assertSame(5, $id);
    }

    public function testFindByCompanyIdReturnsProgress(): void
    {
        $this->connection
            ->expects(self::once())
            ->method('fetchAssociative')
            ->willReturn([
                'id' => 1,
                'company_id' => 42,
                'current_step' => 'address',
                'completed_steps' => '["company_info"]',
                'status' => 'in_progress',
                'created_at' => '2026-06-18T12:00:00Z',
                'updated_at' => '2026-06-18T12:00:00Z',
            ]);

        $progress = $this->repository->findByCompanyId(42);

        self::assertInstanceOf(CompanyRegistrationProgress::class, $progress);
        self::assertSame(42, $progress->companyId);
        self::assertSame(RegistrationStep::ADDRESS, $progress->currentStep);
    }

    public function testFindByCompanyIdReturnsNull(): void
    {
        $this->connection
            ->expects(self::once())
            ->method('fetchAssociative')
            ->willReturn(false);

        self::assertNull($this->repository->findByCompanyId(999));
    }

    public function testUpdateStep(): void
    {
        $this->connection
            ->expects(self::once())
            ->method('executeStatement')
            ->willReturn(1);

        $this->repository->updateStep(1, RegistrationStep::LEADER, [RegistrationStep::COMPANY_INFO, RegistrationStep::ADDRESS]);
    }

    public function testMarkCompleted(): void
    {
        $this->connection
            ->expects(self::once())
            ->method('executeStatement')
            ->willReturn(1);

        $this->repository->markCompleted(1);
    }
}

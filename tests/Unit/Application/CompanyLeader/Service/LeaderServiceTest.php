<?php

namespace App\Tests\Unit\Application\CompanyLeader\Service;

use App\Application\CompanyLeader\Command\CreateLeaderCommand;
use App\Application\CompanyLeader\Command\UpdateLeaderCommand;
use App\Application\CompanyLeader\Service\LeaderService;
use App\Domain\Company\Company;
use App\Domain\CompanyLeader\CompanyLeader;
use App\Infrastructure\Persistence\DbalCompanyRepository;
use App\Infrastructure\Persistence\DbalLeaderRepository;
use App\Shared\Exception\NotFoundException;
use PHPUnit\Framework\TestCase;

class LeaderServiceTest extends TestCase
{
    private DbalLeaderRepository $leaderRepository;
    private DbalCompanyRepository $companyRepository;
    private LeaderService $service;

    protected function setUp(): void
    {
        $this->leaderRepository = $this->createMock(DbalLeaderRepository::class);
        $this->companyRepository = $this->createMock(DbalCompanyRepository::class);
        $this->service = new LeaderService($this->leaderRepository, $this->companyRepository);
    }

    public function testCreateSuccess(): void
    {
        $this->companyRepository
            ->expects(self::once())
            ->method('findById')
            ->with(1001)
            ->willReturn(Company::fromDatabaseRow(['id' => 1001, 'legal_entity' => '7712345678', 'legal_form_name' => 'ООО', 'company_name' => 'Тест', 'country_code' => 'RU', 'status' => 'draft', 'role_code' => null, 'is_kz_nds_applicable' => null, 'is_nds_payer' => null, 'user_uuid' => null, 'created_at' => '2026-01-01T00:00:00Z', 'updated_at' => '2026-01-01T00:00:00Z']));

        $this->leaderRepository
            ->expects(self::once())
            ->method('insert')
            ->willReturn(42);

        $this->leaderRepository
            ->expects(self::once())
            ->method('findById')
            ->with(42)
            ->willReturn(CompanyLeader::fromDatabaseRow([
                'id' => 42,
                'company_id' => 1001,
                'first_name' => 'Иван',
                'last_name' => 'Петров',
                'patronymic' => 'Сергеевич',
                'document_type_code' => 'passport',
            ]));

        $command = new CreateLeaderCommand(
            companyId: 1001,
            firstName: 'Иван',
            lastName: 'Петров',
            documentTypeCode: 'passport',
            patronymic: 'Сергеевич',
        );

        $leader = $this->service->create($command);

        self::assertSame(42, $leader->id);
        self::assertSame('Иван', $leader->firstName);
    }

    public function testCreateCompanyNotFoundThrows(): void
    {
        $this->companyRepository
            ->expects(self::once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('not found');

        $command = new CreateLeaderCommand(
            companyId: 999,
            firstName: 'Иван',
            lastName: 'Петров',
            documentTypeCode: 'passport',
        );

        $this->service->create($command);
    }

    public function testUpdateNotFound(): void
    {
        $this->leaderRepository
            ->expects(self::once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Leader with id 999 not found');

        $command = new UpdateLeaderCommand(
            id: 999,
            companyId: 1001,
            firstName: 'Иван',
            lastName: 'Петров',
            documentTypeCode: 'passport',
        );

        $this->service->update($command);
    }
}

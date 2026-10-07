<?php

namespace App\Tests\Unit\Application\Company\Service;

use App\Application\Company\Command\CreateCompanyCommand;
use App\Application\Company\Command\UpdateCompanyCommand;
use App\Application\Company\Service\CompanyService;
use App\Domain\Company\Company;
use App\Infrastructure\Persistence\DbalCompanyRepository;
use App\Shared\Exception\DuplicateException;
use App\Shared\Exception\NotFoundException;
use PHPUnit\Framework\TestCase;

class CompanyServiceTest extends TestCase
{
    private DbalCompanyRepository $repository;
    private CompanyService $service;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(DbalCompanyRepository::class);
        $this->service = new CompanyService($this->repository);
    }

    public function testCreateSuccess(): void
    {
        $this->repository
            ->expects(self::once())
            ->method('existsByLegalEntity')
            ->with('7712345678')
            ->willReturn(false);

        $this->repository
            ->expects(self::once())
            ->method('insert')
            ->willReturn(42);

        $this->repository
            ->expects(self::once())
            ->method('findById')
            ->with(42)
            ->willReturn(Company::fromDatabaseRow([
                'id' => 42,
                'legal_entity' => '7712345678',
                'legal_form_name' => 'ООО',
                'company_name' => 'Тест',
                'role_code' => 'SELLER',
                'country_code' => 'RU',
                'status' => 'draft',
                'is_kz_nds_applicable' => null,
                'is_nds_payer' => null,
                'user_uuid' => null,
                'created_at' => '2026-06-17T12:00:00Z',
                'updated_at' => '2026-06-17T12:00:00Z',
            ]));

        $command = new CreateCompanyCommand(
            legalEntity: '7712345678',
            legalFormName: 'ООО',
            companyName: 'Тест',
            countryCode: 'RU',
        );

        $company = $this->service->create($command);

        self::assertSame(42, $company->id);
        self::assertSame('7712345678', $company->legalEntity);
    }

    public function testCreateDuplicateLegalEntityThrows(): void
    {
        $this->repository
            ->expects(self::once())
            ->method('existsByLegalEntity')
            ->with('7712345678')
            ->willReturn(true);

        $this->expectException(DuplicateException::class);
        $this->expectExceptionMessage('already exists');

        $command = new CreateCompanyCommand(
            legalEntity: '7712345678',
            legalFormName: 'ООО',
            companyName: 'Тест',
            countryCode: 'RU',
        );

        $this->service->create($command);
    }

    public function testUpdateNotFound(): void
    {
        $this->repository
            ->expects(self::once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('not found');

        $command = new UpdateCompanyCommand(
            id: 999,
            legalEntity: '7712345678',
            legalFormName: 'ООО',
            companyName: 'Тест',
            countryCode: 'RU',
        );

        $this->service->update($command);
    }

    public function testSendToModerationUpdatesStatus(): void
    {
        $this->repository
            ->expects(self::exactly(2))
            ->method('findById')
            ->with(42)
            ->willReturnOnConsecutiveCalls(
                Company::fromDatabaseRow([
                    'id' => 42,
                    'legal_entity' => '7712345678',
                    'legal_form_name' => 'ООО',
                    'company_name' => 'Тест',
                    'role_code' => 'SELLER',
                    'country_code' => 'RU',
                    'status' => 'draft',
                    'is_kz_nds_applicable' => null,
                    'is_nds_payer' => null,
                    'user_uuid' => null,
                    'created_at' => '2026-06-17T12:00:00Z',
                    'updated_at' => '2026-06-17T12:00:00Z',
                ]),
                Company::fromDatabaseRow([
                    'id' => 42,
                    'legal_entity' => '7712345678',
                    'legal_form_name' => 'ООО',
                    'company_name' => 'Тест',
                    'role_code' => 'SELLER',
                    'country_code' => 'RU',
                    'status' => 'moderation',
                    'is_kz_nds_applicable' => null,
                    'is_nds_payer' => null,
                    'user_uuid' => null,
                    'created_at' => '2026-06-17T12:00:00Z',
                    'updated_at' => '2026-06-17T12:00:00Z',
                ]),
            );

        $this->repository
            ->expects(self::once())
            ->method('update')
            ->with(42, self::callback(fn(array $data) => $data['status'] === 'moderation'));

        $company = $this->service->sendToModeration(42);

        self::assertSame('moderation', $company->status);
    }

    public function testSendToModerationNotFound(): void
    {
        $this->repository
            ->expects(self::once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('not found');

        $this->service->sendToModeration(999);
    }
}

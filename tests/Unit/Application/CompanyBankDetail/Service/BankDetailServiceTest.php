<?php

namespace App\Tests\Unit\Application\CompanyBankDetail\Service;

use App\Application\CompanyBankDetail\Command\CreateBankDetailCommand;
use App\Application\CompanyBankDetail\Command\UpdateBankDetailCommand;
use App\Application\CompanyBankDetail\Service\BankDetailService;
use App\Domain\Company\Company;
use App\Domain\CompanyBankDetail\CompanyBankDetail;
use App\Infrastructure\Persistence\DbalBankDetailRepository;
use App\Infrastructure\Persistence\DbalCompanyRepository;
use App\Shared\Exception\NotFoundException;
use PHPUnit\Framework\TestCase;

class BankDetailServiceTest extends TestCase
{
    private DbalBankDetailRepository $bankDetailRepository;
    private DbalCompanyRepository $companyRepository;
    private BankDetailService $service;

    protected function setUp(): void
    {
        $this->bankDetailRepository = $this->createMock(DbalBankDetailRepository::class);
        $this->companyRepository = $this->createMock(DbalCompanyRepository::class);
        $this->service = new BankDetailService($this->bankDetailRepository, $this->companyRepository);
    }

    public function testCreateSuccess(): void
    {
        $this->companyRepository
            ->expects(self::once())
            ->method('findById')
            ->with(123)
            ->willReturn(Company::fromDatabaseRow(['id' => 123, 'legal_entity' => '7712345678', 'legal_form_name' => 'ООО', 'company_name' => 'Тест', 'country_code' => 'RU', 'status' => 'draft', 'role_code' => null, 'is_kz_nds_applicable' => null, 'is_nds_payer' => null, 'user_uuid' => null, 'created_at' => '2026-01-01T00:00:00Z', 'updated_at' => '2026-01-01T00:00:00Z']));

        $this->bankDetailRepository
            ->expects(self::once())
            ->method('insert')
            ->willReturn(42);

        $this->bankDetailRepository
            ->expects(self::once())
            ->method('findById')
            ->with(42)
            ->willReturn(CompanyBankDetail::fromDatabaseRow([
                'id' => 42,
                'company_id' => 123,
                'account_number' => 'KZ123456789012345678',
                'bank_name' => 'Test Bank',
                'bik' => '044525187',
                'swift' => 'DEUTDEFF',
                'correspondent_account' => '30111810400000000700',
                'iban' => 'GB29NWBK60161331926819',
                'country_code' => 'KZ',
            ]));

        $command = new CreateBankDetailCommand(
            companyId: 123,
            accountNumber: 'KZ123456789012345678',
            bankName: 'Test Bank',
            bik: '044525187',
            swift: 'DEUTDEFF',
            correspondentAccount: '30111810400000000700',
            iban: 'GB29NWBK60161331926819',
            countryCode: 'KZ',
        );

        $result = $this->service->create($command);

        self::assertSame(42, $result->id);
        self::assertSame('KZ123456789012345678', $result->accountNumber);
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

        $command = new CreateBankDetailCommand(
            companyId: 999,
            accountNumber: 'KZ123456789012345678',
            bankName: 'Test',
            bik: '044525187',
            swift: 'DEUTDEFF',
            correspondentAccount: '30111810400000000700',
            iban: 'GB29NWBK60161331926819',
            countryCode: 'KZ',
        );

        $this->service->create($command);
    }

    public function testUpdateNotFound(): void
    {
        $this->bankDetailRepository
            ->expects(self::once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Bank detail with id 999 not found');

        $command = new UpdateBankDetailCommand(
            id: 999,
            companyId: 123,
            accountNumber: 'KZ123456789012345678',
            bankName: 'Test',
            bik: '044525187',
            swift: 'DEUTDEFF',
            correspondentAccount: '30111810400000000700',
            iban: 'GB29NWBK60161331926819',
            countryCode: 'KZ',
        );

        $this->service->update($command);
    }
}

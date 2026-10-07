<?php

namespace App\Tests\Unit\Application\CompanyBankDetail\Mapper;

use App\Application\CompanyBankDetail\DTO\BankDetailRawRequest;
use App\Application\CompanyBankDetail\Mapper\BankDetailDomainMapper;
use App\Shared\Exception\ValidationException;
use PHPUnit\Framework\TestCase;

class BankDetailDomainMapperTest extends TestCase
{
    private BankDetailDomainMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new BankDetailDomainMapper();
    }

    public function testMapValidData(): void
    {
        $raw = new BankDetailRawRequest(
            companyId: '123',
            accountNumber: 'KZ123456789012345678',
            bankName: 'АО "Народный Банк Казахстана"',
            bik: '044525187',
            swift: 'DEUTDEFF',
            correspondentAccount: '30111810400000000700',
            iban: 'GB29NWBK60161331926819',
            countryCode: 'KZ',
        );

        $domain = $this->mapper->map($raw);

        self::assertSame(123, $domain->companyId);
        self::assertSame('KZ123456789012345678', $domain->accountNumber);
        self::assertSame('АО "Народный Банк Казахстана"', $domain->bankName);
        self::assertSame('044525187', $domain->bik);
        self::assertSame('DEUTDEFF', $domain->swift);
        self::assertSame('30111810400000000700', $domain->correspondentAccount);
        self::assertSame('GB29NWBK60161331926819', $domain->iban);
        self::assertSame('KZ', $domain->countryCode);
    }

    public function testMapMissingCompanyIdThrows(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('companyId is required');

        $raw = new BankDetailRawRequest(
            accountNumber: 'KZ123456789012345678',
            bankName: 'Test Bank',
            bik: '044525187',
            swift: 'DEUTDEFF',
            correspondentAccount: '30111810400000000700',
            iban: 'GB29NWBK60161331926819',
            countryCode: 'KZ',
        );

        $this->mapper->map($raw);
    }

    public function testMapMissingAccountNumberThrows(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('accountNumber is required');

        $raw = new BankDetailRawRequest(
            companyId: '123',
            bankName: 'Test Bank',
            bik: '044525187',
            swift: 'DEUTDEFF',
            correspondentAccount: '30111810400000000700',
            iban: 'GB29NWBK60161331926819',
            countryCode: 'KZ',
        );

        $this->mapper->map($raw);
    }

    public function testMapMissingBankNameThrows(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('bankName is required');

        $raw = new BankDetailRawRequest(
            companyId: '123',
            accountNumber: 'KZ123456789012345678',
            bik: '044525187',
            swift: 'DEUTDEFF',
            correspondentAccount: '30111810400000000700',
            iban: 'GB29NWBK60161331926819',
            countryCode: 'KZ',
        );

        $this->mapper->map($raw);
    }

    public function testMapMissingBikThrows(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('bik is required');

        $raw = new BankDetailRawRequest(
            companyId: '123',
            accountNumber: 'KZ123456789012345678',
            bankName: 'Test Bank',
            swift: 'DEUTDEFF',
            correspondentAccount: '30111810400000000700',
            iban: 'GB29NWBK60161331926819',
            countryCode: 'KZ',
        );

        $this->mapper->map($raw);
    }

    public function testMapMissingSwiftThrows(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('swift is required');

        $raw = new BankDetailRawRequest(
            companyId: '123',
            accountNumber: 'KZ123456789012345678',
            bankName: 'Test Bank',
            bik: '044525187',
            correspondentAccount: '30111810400000000700',
            iban: 'GB29NWBK60161331926819',
            countryCode: 'KZ',
        );

        $this->mapper->map($raw);
    }

    public function testMapMissingCorrespondentAccountThrows(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('correspondentAccount is required');

        $raw = new BankDetailRawRequest(
            companyId: '123',
            accountNumber: 'KZ123456789012345678',
            bankName: 'Test Bank',
            bik: '044525187',
            swift: 'DEUTDEFF',
            iban: 'GB29NWBK60161331926819',
            countryCode: 'KZ',
        );

        $this->mapper->map($raw);
    }

    public function testMapMissingIbanThrows(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('iban is required');

        $raw = new BankDetailRawRequest(
            companyId: '123',
            accountNumber: 'KZ123456789012345678',
            bankName: 'Test Bank',
            bik: '044525187',
            swift: 'DEUTDEFF',
            correspondentAccount: '30111810400000000700',
            countryCode: 'KZ',
        );

        $this->mapper->map($raw);
    }

    public function testMapMissingCountryCodeThrows(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('countryCode is required');

        $raw = new BankDetailRawRequest(
            companyId: '123',
            accountNumber: 'KZ123456789012345678',
            bankName: 'Test Bank',
            bik: '044525187',
            swift: 'DEUTDEFF',
            correspondentAccount: '30111810400000000700',
            iban: 'GB29NWBK60161331926819',
        );

        $this->mapper->map($raw);
    }
}

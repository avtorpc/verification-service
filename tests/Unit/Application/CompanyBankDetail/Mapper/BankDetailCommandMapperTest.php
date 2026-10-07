<?php

namespace App\Tests\Unit\Application\CompanyBankDetail\Mapper;

use App\Application\CompanyBankDetail\DTO\BankDetailDomainRequest;
use App\Application\CompanyBankDetail\Mapper\BankDetailCommandMapper;
use PHPUnit\Framework\TestCase;

class BankDetailCommandMapperTest extends TestCase
{
    private BankDetailCommandMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new BankDetailCommandMapper();
    }

    public function testMapCreate(): void
    {
        $domain = new BankDetailDomainRequest(
            companyId: 123,
            accountNumber: 'KZ123456789012345678',
            bankName: 'Test Bank',
            bik: '044525187',
            swift: 'DEUTDEFF',
            correspondentAccount: '30111810400000000700',
            iban: 'GB29NWBK60161331926819',
            countryCode: 'KZ',
        );

        $command = $this->mapper->mapCreate($domain);

        self::assertSame(123, $command->companyId);
        self::assertSame('KZ123456789012345678', $command->accountNumber);
        self::assertSame('DEUTDEFF', $command->swift);
        self::assertSame('KZ', $command->countryCode);
    }

    public function testMapUpdate(): void
    {
        $domain = new BankDetailDomainRequest(
            companyId: 123,
            accountNumber: 'RU123456789012345678',
            bankName: 'Test Bank',
            bik: '044525187',
            swift: 'DEUTDEFF',
            correspondentAccount: '30111810400000000700',
            iban: 'GB29NWBK60161331926819',
            countryCode: 'RU',
        );

        $command = $this->mapper->mapUpdate(42, $domain);

        self::assertSame(42, $command->id);
        self::assertSame('RU123456789012345678', $command->accountNumber);
        self::assertSame('RU', $command->countryCode);
    }
}

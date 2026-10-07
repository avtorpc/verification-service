<?php

namespace App\Tests\Unit\Application\CompanyBankDetail\Mapper;

use App\Application\CompanyBankDetail\Mapper\BankDetailJsonMapper;
use PHPUnit\Framework\TestCase;

class BankDetailJsonMapperTest extends TestCase
{
    private BankDetailJsonMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new BankDetailJsonMapper();
    }

    public function testMapFullData(): void
    {
        $data = [
            'companyId' => 123,
            'accountNumber' => 'KZ123456789012345678',
            'bankName' => 'АО "Народный Банк Казахстана"',
            'bik' => '044525187',
            'swift' => 'DEUTDEFF',
            'correspondentAccount' => '30111810400000000700',
            'iban' => 'GB29NWBK60161331926819',
            'countryCode' => 'KZ',
        ];

        $raw = $this->mapper->map($data);

        self::assertSame('123', $raw->companyId);
        self::assertSame('KZ123456789012345678', $raw->accountNumber);
        self::assertSame('АО "Народный Банк Казахстана"', $raw->bankName);
        self::assertSame('044525187', $raw->bik);
        self::assertSame('DEUTDEFF', $raw->swift);
        self::assertSame('30111810400000000700', $raw->correspondentAccount);
        self::assertSame('GB29NWBK60161331926819', $raw->iban);
        self::assertSame('KZ', $raw->countryCode);
    }

    public function testMapMissingFields(): void
    {
        $data = [
            'companyId' => 123,
            'accountNumber' => 'KZ123456789012345678',
            'bankName' => 'Test Bank',
        ];

        $raw = $this->mapper->map($data);

        self::assertNull($raw->bik);
        self::assertNull($raw->swift);
    }
}

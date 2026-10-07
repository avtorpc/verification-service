<?php

namespace App\Tests\Unit\Application\Company\Mapper;

use App\Application\Company\Mapper\CompanyJsonMapper;
use PHPUnit\Framework\TestCase;

class CompanyJsonMapperTest extends TestCase
{
    private CompanyJsonMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new CompanyJsonMapper();
    }

    public function testMapFullData(): void
    {
        $data = [
            'legalEntity' => '7712345678',
            'legalFormName' => 'ООО',
            'companyName' => 'ООО "ТехноПром"',
            'roleCode' => 'SELLER',
            'countryCode' => 'RU',
            'status' => 'draft',
            'isKzNdsApplicable' => true,
            'isNdsPayer' => false,
        ];

        $raw = $this->mapper->map($data);

        self::assertSame('7712345678', $raw->legalEntity);
        self::assertSame('ООО', $raw->legalFormName);
        self::assertSame('ООО "ТехноПром"', $raw->companyName);
        self::assertSame('SELLER', $raw->roleCode);
        self::assertSame('RU', $raw->countryCode);
        self::assertSame('draft', $raw->status);
        self::assertSame('1', $raw->isKzNdsApplicable);
        self::assertSame('', $raw->isNdsPayer);
    }

    public function testMapMinimalData(): void
    {
        $data = [
            'legalEntity' => '7712345678',
            'legalFormName' => 'АО',
            'companyName' => 'Тест',
            'countryCode' => 'KZ',
        ];

        $raw = $this->mapper->map($data);

        self::assertSame('7712345678', $raw->legalEntity);
        self::assertSame('АО', $raw->legalFormName);
        self::assertSame('Тест', $raw->companyName);
        self::assertSame('KZ', $raw->countryCode);
        self::assertNull($raw->roleCode);
        self::assertNull($raw->status);
        self::assertNull($raw->isKzNdsApplicable);
        self::assertNull($raw->isNdsPayer);
    }

    public function testMapCamelCaseKeys(): void
    {
        $data = [
            'legalEntity' => '7712345678',
            'legalFormName' => 'ООО',
            'companyName' => 'ООО "ТехноПром"',
            'countryCode' => 'RU',
        ];

        $raw = $this->mapper->map($data);

        self::assertSame('7712345678', $raw->legalEntity);
        self::assertSame('ООО', $raw->legalFormName);
        self::assertSame('ООО "ТехноПром"', $raw->companyName);
        self::assertSame('RU', $raw->countryCode);
    }
}

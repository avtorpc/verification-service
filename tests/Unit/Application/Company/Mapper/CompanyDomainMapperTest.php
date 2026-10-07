<?php

namespace App\Tests\Unit\Application\Company\Mapper;

use App\Application\Company\DTO\CompanyRawRequest;
use App\Application\Company\Mapper\CompanyDomainMapper;
use App\Shared\Exception\ValidationException;
use PHPUnit\Framework\TestCase;

class CompanyDomainMapperTest extends TestCase
{
    private CompanyDomainMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new CompanyDomainMapper();
    }

    public function testMapValidData(): void
    {
        $raw = new CompanyRawRequest(
            legalEntity: '7712345678',
            legalFormName: 'ООО',
            companyName: 'ООО "ТехноПром"',
            roleCode: 'SELLER',
            countryCode: 'RU',
            status: 'draft',
        );

        $domain = $this->mapper->map($raw);

        self::assertSame('7712345678', $domain->legalEntity);
        self::assertSame('ООО', $domain->legalFormName);
        self::assertSame('ООО "ТехноПром"', $domain->companyName);
        self::assertSame('SELLER', $domain->roleCode);
        self::assertSame('RU', $domain->countryCode);
        self::assertSame('draft', $domain->status);
        self::assertNull($domain->isKzNdsApplicable);
        self::assertNull($domain->isNdsPayer);
    }

    public function testMapMissingLegalEntityThrows(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('legalEntity is required');

        $raw = new CompanyRawRequest(
            countryCode: 'RU',
            companyName: 'Тест',
            legalFormName: 'ООО',
        );

        $this->mapper->map($raw);
    }

    public function testMapMissingCountryCodeThrows(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('countryCode is required');

        $raw = new CompanyRawRequest(
            legalEntity: '7712345678',
            companyName: 'Тест',
            legalFormName: 'ООО',
        );

        $this->mapper->map($raw);
    }

    public function testMapMissingCompanyNameThrows(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('companyName is required');

        $raw = new CompanyRawRequest(
            legalEntity: '7712345678',
            countryCode: 'RU',
            legalFormName: 'ООО',
        );

        $this->mapper->map($raw);
    }

    public function testMapMissingLegalFormNameThrows(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('legalFormName is required');

        $raw = new CompanyRawRequest(
            legalEntity: '7712345678',
            companyName: 'Тест',
            countryCode: 'RU',
        );

        $this->mapper->map($raw);
    }

    public function testMapRoleCodeOptional(): void
    {
        $raw = new CompanyRawRequest(
            legalEntity: '7712345678',
            legalFormName: 'ООО',
            companyName: 'Тест',
            countryCode: 'RU',
        );

        $domain = $this->mapper->map($raw);

        self::assertNull($domain->roleCode);
    }

    public function testMapInvalidStatusThrows(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid status');

        $raw = new CompanyRawRequest(
            legalEntity: '7712345678',
            legalFormName: 'ООО',
            companyName: 'Тест',
            countryCode: 'RU',
            status: 'invalid',
        );

        $this->mapper->map($raw);
    }

    public function testMapInvalidStatusActiveNoLongerAllowed(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid status');

        $raw = new CompanyRawRequest(
            legalEntity: '7712345678',
            legalFormName: 'ООО',
            companyName: 'Тест',
            countryCode: 'RU',
            status: 'active',
        );

        $this->mapper->map($raw);
    }

    public function testMapApprovedStatusAllowed(): void
    {
        $raw = new CompanyRawRequest(
            legalEntity: '7712345678',
            legalFormName: 'ООО',
            companyName: 'Тест',
            countryCode: 'RU',
            status: 'approved',
        );

        $domain = $this->mapper->map($raw);

        self::assertSame('approved', $domain->status);
    }

    public function testMapDefaultStatusIsDraft(): void
    {
        $raw = new CompanyRawRequest(
            legalEntity: '7712345678',
            legalFormName: 'ООО',
            companyName: 'Тест',
            countryCode: 'RU',
        );

        $domain = $this->mapper->map($raw);

        self::assertSame('draft', $domain->status);
    }
}

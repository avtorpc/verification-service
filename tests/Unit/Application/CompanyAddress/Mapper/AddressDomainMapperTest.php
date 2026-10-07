<?php

namespace App\Tests\Unit\Application\CompanyAddress\Mapper;

use App\Application\CompanyAddress\DTO\AddressRawRequest;
use App\Application\CompanyAddress\Mapper\AddressDomainMapper;
use App\Shared\Exception\ValidationException;
use PHPUnit\Framework\TestCase;

class AddressDomainMapperTest extends TestCase
{
    private AddressDomainMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new AddressDomainMapper();
    }

    public function testMapValidLegalAddress(): void
    {
        $raw = new AddressRawRequest(
            companyId: '1001',
            addressType: 'legal',
            countryCode: 'RU',
            region: 'Московская область',
            city: 'Москва',
            street: 'ул. Ленина, д. 15',
            house: '15',
            apartment: 'офис 305',
            zipCode: '125009',
        );

        $domain = $this->mapper->map($raw);

        self::assertSame(1001, $domain->companyId);
        self::assertSame('legal', $domain->addressType);
        self::assertSame('RU', $domain->countryCode);
        self::assertSame('Москва', $domain->city);
        self::assertSame('офис 305', $domain->apartment);
        self::assertSame('125009', $domain->zipCode);
        self::assertNull($domain->isSameAsLegal);
    }

    public function testMapValidPostalAddress(): void
    {
        $raw = new AddressRawRequest(
            companyId: '1001',
            addressType: 'postal',
            countryCode: 'KZ',
            region: 'Алматинская область',
            city: 'Алматы',
            street: 'ул. Абая',
            house: '7',
            zipCode: '050000',
            isSameAsLegal: '1',
        );

        $domain = $this->mapper->map($raw);

        self::assertSame('postal', $domain->addressType);
        self::assertTrue($domain->isSameAsLegal);
    }

    public function testMapMissingCompanyIdThrows(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('companyId is required');

        $raw = new AddressRawRequest(
            addressType: 'legal',
            countryCode: 'RU',
            region: 'Москва',
            city: 'Москва',
            street: 'ул. Тест',
            house: '1',
            zipCode: '125009',
        );

        $this->mapper->map($raw);
    }

    public function testMapInvalidAddressTypeThrows(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('addressType is required');

        $raw = new AddressRawRequest(
            companyId: '1001',
            addressType: 'invalid',
            countryCode: 'RU',
            region: 'Москва',
            city: 'Москва',
            street: 'ул. Тест',
            house: '1',
            zipCode: '125009',
        );

        $this->mapper->map($raw);
    }

    public function testMapMissingRequiredFieldsThrows(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('region is required');

        $raw = new AddressRawRequest(
            companyId: '1001',
            addressType: 'legal',
            countryCode: 'RU',
            city: 'Москва',
            street: 'ул. Тест',
            house: '1',
            zipCode: '125009',
        );

        $this->mapper->map($raw);
    }

    public function testMapMissingZipCodeThrows(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('zipCode is required');

        $raw = new AddressRawRequest(
            companyId: '1001',
            addressType: 'legal',
            countryCode: 'RU',
            region: 'Москва',
            city: 'Москва',
            street: 'ул. Тест',
            house: '1',
        );

        $this->mapper->map($raw);
    }
}

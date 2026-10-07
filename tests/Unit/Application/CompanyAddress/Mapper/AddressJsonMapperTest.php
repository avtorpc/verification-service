<?php

namespace App\Tests\Unit\Application\CompanyAddress\Mapper;

use App\Application\CompanyAddress\Mapper\AddressJsonMapper;
use PHPUnit\Framework\TestCase;

class AddressJsonMapperTest extends TestCase
{
    private AddressJsonMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new AddressJsonMapper();
    }

    public function testMapFullData(): void
    {
        $data = [
            'companyId' => 1001,
            'addressType' => 'legal',
            'countryCode' => 'RU',
            'region' => 'Московская область',
            'city' => 'Москва',
            'street' => 'ул. Ленина, д. 15',
            'house' => '15',
            'apartment' => 'офис 305',
            'zipCode' => '125009',
            'isSameAsLegal' => false,
        ];

        $raw = $this->mapper->map($data);

        self::assertSame('1001', $raw->companyId);
        self::assertSame('legal', $raw->addressType);
        self::assertSame('RU', $raw->countryCode);
        self::assertSame('Московская область', $raw->region);
        self::assertSame('Москва', $raw->city);
        self::assertSame('ул. Ленина, д. 15', $raw->street);
        self::assertSame('15', $raw->house);
        self::assertSame('офис 305', $raw->apartment);
        self::assertSame('125009', $raw->zipCode);
        self::assertSame('', $raw->isSameAsLegal);
    }

    public function testMapMinimalData(): void
    {
        $data = [
            'companyId' => 1001,
            'addressType' => 'postal',
            'countryCode' => 'KZ',
            'region' => 'Алматинская область',
            'city' => 'Алматы',
            'street' => 'ул. Абая',
            'house' => '7',
            'zipCode' => '050000',
        ];

        $raw = $this->mapper->map($data);

        self::assertSame('1001', $raw->companyId);
        self::assertSame('postal', $raw->addressType);
        self::assertNull($raw->apartment);
        self::assertNull($raw->isSameAsLegal);
    }

    public function testMapCamelCaseKeys(): void
    {
        $data = [
            'companyId' => '500',
            'addressType' => 'legal',
            'countryCode' => 'CN',
            'region' => 'Пекин',
            'city' => 'Пекин',
            'street' => 'ул. Чанъань',
            'house' => '1',
            'zipCode' => '100000',
        ];

        $raw = $this->mapper->map($data);

        self::assertSame('500', $raw->companyId);
        self::assertSame('CN', $raw->countryCode);
        self::assertSame('100000', $raw->zipCode);
    }
}

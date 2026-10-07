<?php

namespace App\Tests\Unit\Application\CompanyAddress\Mapper;

use App\Application\CompanyAddress\DTO\AddressDomainRequest;
use App\Application\CompanyAddress\Mapper\AddressCommandMapper;
use PHPUnit\Framework\TestCase;

class AddressCommandMapperTest extends TestCase
{
    private AddressCommandMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new AddressCommandMapper();
    }

    public function testMapCreate(): void
    {
        $domain = new AddressDomainRequest(
            companyId: 1001,
            addressType: 'legal',
            countryCode: 'RU',
            region: 'Московская область',
            city: 'Москва',
            street: 'ул. Ленина, д. 15',
            house: '15',
            zipCode: '125009',
        );

        $command = $this->mapper->mapCreate($domain);

        self::assertSame(1001, $command->companyId);
        self::assertSame('legal', $command->addressType);
        self::assertSame('RU', $command->countryCode);
        self::assertNull($command->apartment);
        self::assertNull($command->isSameAsLegal);
    }

    public function testMapUpdate(): void
    {
        $domain = new AddressDomainRequest(
            companyId: 1001,
            addressType: 'postal',
            countryCode: 'KZ',
            region: 'Алматинская область',
            city: 'Алматы',
            street: 'ул. Абая',
            house: '7',
            zipCode: '050000',
            isSameAsLegal: true,
        );

        $command = $this->mapper->mapUpdate(42, $domain);

        self::assertSame(42, $command->id);
        self::assertSame('postal', $command->addressType);
        self::assertTrue($command->isSameAsLegal);
    }
}

<?php

namespace App\Tests\Unit\Application\CompanyAddress\Service;

use App\Application\CompanyAddress\Command\CreateAddressCommand;
use App\Application\CompanyAddress\Command\UpdateAddressCommand;
use App\Application\CompanyAddress\Service\AddressService;
use App\Domain\Company\Company;
use App\Domain\CompanyAddress\CompanyAddress;
use App\Infrastructure\Persistence\DbalAddressRepository;
use App\Infrastructure\Persistence\DbalCompanyRepository;
use App\Shared\Exception\NotFoundException;
use PHPUnit\Framework\TestCase;

class AddressServiceTest extends TestCase
{
    private DbalAddressRepository $addressRepository;
    private DbalCompanyRepository $companyRepository;
    private AddressService $service;

    protected function setUp(): void
    {
        $this->addressRepository = $this->createMock(DbalAddressRepository::class);
        $this->companyRepository = $this->createMock(DbalCompanyRepository::class);
        $this->service = new AddressService($this->addressRepository, $this->companyRepository);
    }

    public function testCreateSuccess(): void
    {
        $this->companyRepository
            ->expects(self::once())
            ->method('findById')
            ->with(1001)
            ->willReturn(Company::fromDatabaseRow(['id' => 1001, 'legal_entity' => '7712345678', 'legal_form_name' => 'ООО', 'company_name' => 'Тест', 'country_code' => 'RU', 'status' => 'draft', 'role_code' => null, 'is_kz_nds_applicable' => null, 'is_nds_payer' => null, 'user_uuid' => null, 'created_at' => '2026-01-01T00:00:00Z', 'updated_at' => '2026-01-01T00:00:00Z']));

        $this->addressRepository
            ->expects(self::once())
            ->method('insert')
            ->willReturn(42);

        $this->addressRepository
            ->expects(self::once())
            ->method('findById')
            ->with(42)
            ->willReturn(CompanyAddress::fromDatabaseRow([
                'id' => 42,
                'company_id' => 1001,
                'address_type' => 'legal',
                'country_code' => 'RU',
                'region' => 'Московская область',
                'city' => 'Москва',
                'street' => 'ул. Ленина, д. 15',
                'house' => '15',
                'apartment' => null,
                'zip_code' => '125009',
                'is_same_as_legal' => null,
            ]));

        $command = new CreateAddressCommand(
            companyId: 1001,
            addressType: 'legal',
            countryCode: 'RU',
            region: 'Московская область',
            city: 'Москва',
            street: 'ул. Ленина, д. 15',
            house: '15',
            zipCode: '125009',
        );

        $address = $this->service->create($command);

        self::assertSame(42, $address->id);
        self::assertSame('legal', $address->addressType);
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

        $command = new CreateAddressCommand(
            companyId: 999,
            addressType: 'legal',
            countryCode: 'RU',
            region: 'Москва',
            city: 'Москва',
            street: 'ул. Тест',
            house: '1',
            zipCode: '125009',
        );

        $this->service->create($command);
    }

    public function testUpdateNotFound(): void
    {
        $this->addressRepository
            ->expects(self::once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Address with id 999 not found');

        $command = new UpdateAddressCommand(
            id: 999,
            companyId: 1001,
            addressType: 'legal',
            countryCode: 'RU',
            region: 'Москва',
            city: 'Москва',
            street: 'ул. Тест',
            house: '1',
            zipCode: '125009',
        );

        $this->service->update($command);
    }
}

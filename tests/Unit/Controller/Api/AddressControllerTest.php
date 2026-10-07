<?php

namespace App\Tests\Unit\Controller\Api;

use App\Application\CompanyAddress\Mapper\AddressCommandMapper;
use App\Application\CompanyAddress\Mapper\AddressDomainMapper;
use App\Application\CompanyAddress\Mapper\AddressJsonMapper;
use App\Application\CompanyAddress\Service\AddressService;
use App\Controller\Api\AddressController;
use App\Domain\CompanyAddress\CompanyAddress;
use App\Shared\Exception\NotFoundException;
use App\Shared\Exception\ValidationException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class AddressControllerTest extends TestCase
{
    private AddressJsonMapper $jsonMapper;
    private AddressDomainMapper $domainMapper;
    private AddressCommandMapper $commandMapper;
    private AddressService $addressService;
    private AddressController $controller;

    protected function setUp(): void
    {
        $this->jsonMapper = new AddressJsonMapper();
        $this->domainMapper = new AddressDomainMapper();
        $this->commandMapper = new AddressCommandMapper();
        $this->addressService = $this->createMock(AddressService::class);
        $this->controller = new AddressController(
            $this->jsonMapper,
            $this->domainMapper,
            $this->commandMapper,
            $this->addressService,
        );
    }

    public function testCreateSuccess(): void
    {
        $this->addressService
            ->expects(self::once())
            ->method('create')
            ->willReturn(CompanyAddress::fromDatabaseRow([
                'id' => 2001,
                'company_id' => 1001,
                'address_type' => 'legal',
                'country_code' => 'RU',
                'region' => 'Московская область',
                'city' => 'Москва',
                'street' => 'ул. Ленина, д. 15',
                'house' => '15',
                'apartment' => 'офис 305',
                'zip_code' => '125009',
                'is_same_as_legal' => null,
            ]));

        $request = new Request(
            content: json_encode([
                'companyId' => 1001,
                'addressType' => 'legal',
                'countryCode' => 'RU',
                'region' => 'Московская область',
                'city' => 'Москва',
                'street' => 'ул. Ленина, д. 15',
                'house' => '15',
                'apartment' => 'офис 305',
                'zipCode' => '125009',
            ])
        );

        $response = $this->controller->create($request);

        self::assertSame(201, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        self::assertTrue($body['success']);
        self::assertSame('Запись адреса компании создана', $body['message']);
        self::assertArrayHasKey('timestamp', $body);
        self::assertArrayHasKey('expiresInSeconds', $body);
        self::assertSame(2001, $body['data']['id']);
        self::assertSame('legal', $body['data']['addressType']);
        self::assertSame('RU', $body['data']['countryCode']);
    }

    public function testCreateValidationError(): void
    {
        $request = new Request(
            content: json_encode([
                'companyId' => 1001,
                'addressType' => 'invalid',
            ])
        );

        $this->expectException(ValidationException::class);
        $this->controller->create($request);
    }

    public function testUpdateSuccess(): void
    {
        $this->addressService
            ->expects(self::once())
            ->method('update')
            ->willReturn(CompanyAddress::fromDatabaseRow([
                'id' => 2001,
                'company_id' => 1001,
                'address_type' => 'postal',
                'country_code' => 'KZ',
                'region' => 'Алматинская область',
                'city' => 'Алматы',
                'street' => 'ул. Абая',
                'house' => '7',
                'apartment' => null,
                'zip_code' => '050000',
                'is_same_as_legal' => true,
            ]));

        $request = new Request(
            content: json_encode([
                'companyId' => 1001,
                'addressType' => 'postal',
                'countryCode' => 'KZ',
                'region' => 'Алматинская область',
                'city' => 'Алматы',
                'street' => 'ул. Абая',
                'house' => '7',
                'zipCode' => '050000',
                'isSameAsLegal' => true,
            ])
        );

        $response = $this->controller->update(2001, $request);

        self::assertSame(200, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        self::assertTrue($body['success']);
        self::assertSame('Запись адреса компании обновлена', $body['message']);
        self::assertSame(2001, $body['data']['id']);
        self::assertTrue($body['data']['isSameAsLegal']);
    }

    public function testUpdateNotFound(): void
    {
        $this->addressService
            ->method('update')
            ->willThrowException(new NotFoundException('Address with id 9999 not found'));

        $request = new Request(
            content: json_encode([
                'companyId' => 1001,
                'addressType' => 'legal',
                'countryCode' => 'RU',
                'region' => 'Москва',
                'city' => 'Москва',
                'street' => 'ул. Тест',
                'house' => '1',
                'zipCode' => '125009',
            ])
        );

        $this->expectException(NotFoundException::class);
        $this->controller->update(9999, $request);
    }

    public function testCreateCompanyNotFoundReturns404(): void
    {
        $this->addressService
            ->method('create')
            ->willThrowException(new NotFoundException('Company with id 999 not found'));

        $request = new Request(
            content: json_encode([
                'companyId' => 999,
                'addressType' => 'legal',
                'countryCode' => 'RU',
                'region' => 'Москва',
                'city' => 'Москва',
                'street' => 'ул. Тест',
                'house' => '1',
                'zipCode' => '125009',
            ])
        );

        $this->expectException(NotFoundException::class);
        $this->controller->create($request);
    }
}

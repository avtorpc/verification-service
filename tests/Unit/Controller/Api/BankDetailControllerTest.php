<?php

namespace App\Tests\Unit\Controller\Api;

use App\Application\CompanyBankDetail\Mapper\BankDetailCommandMapper;
use App\Application\CompanyBankDetail\Mapper\BankDetailDomainMapper;
use App\Application\CompanyBankDetail\Mapper\BankDetailJsonMapper;
use App\Application\CompanyBankDetail\Service\BankDetailService;
use App\Controller\Api\BankDetailController;
use App\Domain\CompanyBankDetail\CompanyBankDetail;
use App\Shared\Exception\NotFoundException;
use App\Shared\Exception\ValidationException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class BankDetailControllerTest extends TestCase
{
    private BankDetailJsonMapper $jsonMapper;
    private BankDetailDomainMapper $domainMapper;
    private BankDetailCommandMapper $commandMapper;
    private BankDetailService $bankDetailService;
    private BankDetailController $controller;

    protected function setUp(): void
    {
        $this->jsonMapper = new BankDetailJsonMapper();
        $this->domainMapper = new BankDetailDomainMapper();
        $this->commandMapper = new BankDetailCommandMapper();
        $this->bankDetailService = $this->createMock(BankDetailService::class);
        $this->controller = new BankDetailController(
            $this->jsonMapper,
            $this->domainMapper,
            $this->commandMapper,
            $this->bankDetailService,
        );
    }

    public function testCreateSuccess(): void
    {
        $this->bankDetailService
            ->expects(self::once())
            ->method('create')
            ->willReturn(CompanyBankDetail::fromDatabaseRow([
                'id' => 5003,
                'company_id' => 123,
                'account_number' => 'KZ123456789012345678',
                'bank_name' => 'АО "Народный Банк Казахстана"',
                'bik' => '044525187',
                'swift' => 'DEUTDEFF',
                'correspondent_account' => '30111810400000000700',
                'iban' => 'GB29NWBK60161331926819',
                'country_code' => 'KZ',
            ]));

        $request = new Request(
            content: json_encode([
                'companyId' => 123,
                'accountNumber' => 'KZ123456789012345678',
                'bankName' => 'АО "Народный Банк Казахстана"',
                'bik' => '044525187',
                'swift' => 'DEUTDEFF',
                'correspondentAccount' => '30111810400000000700',
                'iban' => 'GB29NWBK60161331926819',
                'countryCode' => 'KZ',
            ])
        );

        $response = $this->controller->create($request);

        self::assertSame(201, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        self::assertTrue($body['success']);
        self::assertSame('Запись банковских реквизитов компании создана', $body['message']);
        self::assertSame(5003, $body['data']['id']);
        self::assertSame('KZ123456789012345678', $body['data']['accountNumber']);
        self::assertSame('DEUTDEFF', $body['data']['swift']);
        self::assertSame('KZ', $body['data']['countryCode']);
    }

    public function testCreateValidationError(): void
    {
        $request = new Request(
            content: json_encode([
                'companyId' => 123,
            ])
        );

        $this->expectException(ValidationException::class);
        $this->controller->create($request);
    }

    public function testUpdateSuccess(): void
    {
        $this->bankDetailService
            ->expects(self::once())
            ->method('update')
            ->willReturn(CompanyBankDetail::fromDatabaseRow([
                'id' => 5003,
                'company_id' => 123,
                'account_number' => 'RU123456789012345678',
                'bank_name' => 'Сбербанк',
                'bik' => '044525225',
                'swift' => 'SBERDEFF',
                'correspondent_account' => '30111810400000000701',
                'iban' => 'GB29NWBK60161331926820',
                'country_code' => 'RU',
            ]));

        $request = new Request(
            content: json_encode([
                'companyId' => 123,
                'accountNumber' => 'RU123456789012345678',
                'bankName' => 'Сбербанк',
                'bik' => '044525225',
                'swift' => 'SBERDEFF',
                'correspondentAccount' => '30111810400000000701',
                'iban' => 'GB29NWBK60161331926820',
                'countryCode' => 'RU',
            ])
        );

        $response = $this->controller->update(5003, $request);

        self::assertSame(200, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        self::assertTrue($body['success']);
        self::assertSame('Запись банковских реквизитов компании изменена', $body['message']);
        self::assertSame('RU', $body['data']['countryCode']);
    }

    public function testUpdateNotFound(): void
    {
        $this->bankDetailService
            ->method('update')
            ->willThrowException(new NotFoundException('Bank detail with id 999 not found'));

        $request = new Request(
            content: json_encode([
                'companyId' => 123,
                'accountNumber' => 'KZ123456789012345678',
                'bankName' => 'Test',
                'bik' => '044525187',
                'swift' => 'DEUTDEFF',
                'correspondentAccount' => '30111810400000000700',
                'iban' => 'GB29NWBK60161331926819',
                'countryCode' => 'KZ',
            ])
        );

        $this->expectException(NotFoundException::class);
        $this->controller->update(999, $request);
    }
}

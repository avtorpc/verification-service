<?php

namespace App\Tests\Unit\Controller\Api;

use App\Application\Company\Mapper\CompanyCommandMapper;
use App\Application\Company\Mapper\CompanyDomainMapper;
use App\Application\Company\Mapper\CompanyJsonMapper;
use App\Application\Company\Service\CompanyService;
use App\Controller\Api\CompanyController;
use App\Domain\Company\Company;
use App\Shared\Exception\NotFoundException;
use App\Shared\Exception\ValidationException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class CompanyControllerTest extends TestCase
{
    private CompanyJsonMapper $jsonMapper;
    private CompanyDomainMapper $domainMapper;
    private CompanyCommandMapper $commandMapper;
    private CompanyService $companyService;
    private CompanyController $controller;

    protected function setUp(): void
    {
        $this->jsonMapper = new CompanyJsonMapper();
        $this->domainMapper = new CompanyDomainMapper();
        $this->commandMapper = new CompanyCommandMapper();
        $this->companyService = $this->createMock(CompanyService::class);
        $this->controller = new CompanyController(
            $this->jsonMapper,
            $this->domainMapper,
            $this->commandMapper,
            $this->companyService,
        );
    }

    public function testCreateSuccess(): void
    {
        $this->companyService
            ->expects(self::once())
            ->method('create')
            ->willReturn(Company::fromDatabaseRow([
                'id' => 1,
                'legal_entity' => '7712345678',
                'legal_form_name' => 'ООО',
                'company_name' => 'ООО "ТехноПром"',
                'role_code' => 'SELLER',
                'country_code' => 'RU',
                'status' => 'draft',
                'is_kz_nds_applicable' => null,
                'is_nds_payer' => null,
                'user_uuid' => null,
                'created_at' => '2026-06-17T12:00:00Z',
                'updated_at' => '2026-06-17T12:00:00Z',
            ]));

        $request = new Request(
            content: json_encode([
                'legalEntity' => '7712345678',
                'legalFormName' => 'ООО',
                'companyName' => 'ООО "ТехноПром"',
                'roleCode' => 'SELLER',
                'countryCode' => 'RU',
            ])
        );

        $response = $this->controller->create($request);

        self::assertSame(201, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        self::assertTrue($data['success']);
        self::assertArrayHasKey('timestamp', $data);
        self::assertSame('Компания создана', $data['message']);
        self::assertSame(1, $data['data']['id']);
        self::assertSame('7712345678', $data['data']['legalEntity']);
        self::assertSame('ООО', $data['data']['legalFormName']);
        self::assertSame('SELLER', $data['data']['roleCode']);
        self::assertSame('RU', $data['data']['countryCode']);
        self::assertArrayHasKey('createdAt', $data['data']);
        self::assertArrayHasKey('updatedAt', $data['data']);
    }

    public function testCreateValidationError(): void
    {
        $request = new Request(
            content: json_encode([
                'companyName' => 'Тест',
            ])
        );

        $this->expectException(ValidationException::class);
        $this->controller->create($request);
    }

    public function testUpdateNotFound(): void
    {
        $this->companyService
            ->method('update')
            ->willThrowException(new NotFoundException('Company with id 999 not found'));

        $request = new Request(
            content: json_encode([
                'legalEntity' => '7712345678',
                'legalFormName' => 'ООО',
                'companyName' => 'Тест',
                'countryCode' => 'RU',
            ])
        );

        $this->expectException(NotFoundException::class);
        $this->controller->update(999, $request);
    }

    public function testSendToModerationSuccess(): void
    {
        $this->companyService
            ->expects(self::once())
            ->method('sendToModeration')
            ->with(42)
            ->willReturn(Company::fromDatabaseRow([
                'id' => 42,
                'legal_entity' => '7712345678',
                'legal_form_name' => 'ООО',
                'company_name' => 'Тест',
                'role_code' => 'SELLER',
                'country_code' => 'RU',
                'status' => 'moderation',
                'is_kz_nds_applicable' => null,
                'is_nds_payer' => null,
                'user_uuid' => null,
                'created_at' => '2026-06-17T12:00:00Z',
                'updated_at' => '2026-06-17T12:00:00Z',
            ]));

        $response = $this->controller->sendToModeration(42);

        self::assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        self::assertTrue($data['success']);
        self::assertSame('Компания отправлена на модерацию', $data['message']);
        self::assertSame('moderation', $data['data']['status']);
    }

    public function testSendToModerationNotFound(): void
    {
        $this->companyService
            ->expects(self::once())
            ->method('sendToModeration')
            ->with(999)
            ->willThrowException(new NotFoundException('Company with id 999 not found'));

        $this->expectException(NotFoundException::class);
        $this->controller->sendToModeration(999);
    }

    public function testCreateReturns201WithFullBody(): void
    {
        $this->companyService
            ->expects(self::once())
            ->method('create')
            ->willReturn(Company::fromDatabaseRow([
                'id' => 123,
                'legal_entity' => '7712345678',
                'legal_form_name' => 'ООО',
                'company_name' => 'ООО "ТехноПром"',
                'role_code' => 'SELLER',
                'country_code' => 'RU',
                'status' => 'draft',
                'is_kz_nds_applicable' => null,
                'is_nds_payer' => true,
                'user_uuid' => null,
                'created_at' => '2026-04-28T14:22:01Z',
                'updated_at' => '2026-04-28T14:22:01Z',
            ]));

        $request = new Request(
            content: json_encode([
                'legalEntity' => '7712345678',
                'legalFormName' => 'ООО',
                'companyName' => 'ООО "ТехноПром"',
                'roleCode' => 'SELLER',
                'countryCode' => 'RU',
                'isNdsPayer' => true,
            ])
        );

        $response = $this->controller->create($request);

        self::assertSame(201, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);

        self::assertTrue($data['success']);
        self::assertSame(123, $data['data']['id']);
        self::assertSame('7712345678', $data['data']['legalEntity']);
        self::assertSame('ООО', $data['data']['legalFormName']);
        self::assertSame('ООО "ТехноПром"', $data['data']['companyName']);
        self::assertSame('SELLER', $data['data']['roleCode']);
        self::assertSame('RU', $data['data']['countryCode']);
        self::assertSame('draft', $data['data']['status']);
        self::assertTrue($data['data']['isNdsPayer']);
        self::assertNull($data['data']['isKzNdsApplicable']);
        self::assertSame('2026-04-28T14:22:01Z', $data['data']['createdAt']);
        self::assertSame('2026-04-28T14:22:01Z', $data['data']['updatedAt']);
    }
}

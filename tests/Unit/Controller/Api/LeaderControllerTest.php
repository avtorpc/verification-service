<?php

namespace App\Tests\Unit\Controller\Api;

use App\Application\CompanyLeader\Mapper\LeaderCommandMapper;
use App\Application\CompanyLeader\Mapper\LeaderDomainMapper;
use App\Application\CompanyLeader\Mapper\LeaderJsonMapper;
use App\Application\CompanyLeader\Service\LeaderService;
use App\Controller\Api\LeaderController;
use App\Domain\CompanyLeader\CompanyLeader;
use App\Shared\Exception\NotFoundException;
use App\Shared\Exception\ValidationException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class LeaderControllerTest extends TestCase
{
    private LeaderJsonMapper $jsonMapper;
    private LeaderDomainMapper $domainMapper;
    private LeaderCommandMapper $commandMapper;
    private LeaderService $leaderService;
    private LeaderController $controller;

    protected function setUp(): void
    {
        $this->jsonMapper = new LeaderJsonMapper();
        $this->domainMapper = new LeaderDomainMapper();
        $this->commandMapper = new LeaderCommandMapper();
        $this->leaderService = $this->createMock(LeaderService::class);
        $this->controller = new LeaderController(
            $this->jsonMapper,
            $this->domainMapper,
            $this->commandMapper,
            $this->leaderService,
        );
    }

    public function testCreateSuccess(): void
    {
        $this->leaderService
            ->expects(self::once())
            ->method('create')
            ->willReturn(CompanyLeader::fromDatabaseRow([
                'id' => 88,
                'company_id' => 123,
                'first_name' => 'Иван',
                'last_name' => 'Петров',
                'patronymic' => 'Сергеевич',
                'document_type_code' => 'passport',
            ]));

        $request = new Request(
            content: json_encode([
                'companyId' => 123,
                'firstName' => 'Иван',
                'lastName' => 'Петров',
                'patronymic' => 'Сергеевич',
                'documentTypeCode' => 'passport',
            ])
        );

        $response = $this->controller->create($request);

        self::assertSame(201, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        self::assertTrue($body['success']);
        self::assertSame('Запись руководителя компании создана', $body['message']);
        self::assertArrayHasKey('timestamp', $body);
        self::assertSame(600, $body['expiresInSeconds']);
        self::assertSame(88, $body['data']['id']);
        self::assertSame('Иван', $body['data']['firstName']);
        self::assertSame('passport', $body['data']['documentTypeCode']);
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
        $this->leaderService
            ->expects(self::once())
            ->method('update')
            ->willReturn(CompanyLeader::fromDatabaseRow([
                'id' => 88,
                'company_id' => 123,
                'first_name' => 'Петр',
                'last_name' => 'Иванов',
                'patronymic' => null,
                'document_type_code' => 'passport',
            ]));

        $request = new Request(
            content: json_encode([
                'companyId' => 123,
                'firstName' => 'Петр',
                'lastName' => 'Иванов',
                'documentTypeCode' => 'passport',
            ])
        );

        $response = $this->controller->update(88, $request);

        self::assertSame(200, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        self::assertTrue($body['success']);
        self::assertSame('Запись руководителя компании обновлена', $body['message']);
        self::assertSame(88, $body['data']['id']);
        self::assertNull($body['data']['patronymic']);
    }

    public function testUpdateNotFound(): void
    {
        $this->leaderService
            ->method('update')
            ->willThrowException(new NotFoundException('Leader with id 999 not found'));

        $request = new Request(
            content: json_encode([
                'companyId' => 123,
                'firstName' => 'Иван',
                'lastName' => 'Петров',
                'documentTypeCode' => 'passport',
            ])
        );

        $this->expectException(NotFoundException::class);
        $this->controller->update(999, $request);
    }

    public function testCreateCompanyNotFoundReturns404(): void
    {
        $this->leaderService
            ->method('create')
            ->willThrowException(new NotFoundException('Company with id 999 not found'));

        $request = new Request(
            content: json_encode([
                'companyId' => 999,
                'firstName' => 'Иван',
                'lastName' => 'Петров',
                'documentTypeCode' => 'passport',
            ])
        );

        $this->expectException(NotFoundException::class);
        $this->controller->create($request);
    }
}

<?php

namespace App\Tests\Unit\Controller\Api;

use App\Application\CompanyContact\Mapper\ContactCommandMapper;
use App\Application\CompanyContact\Mapper\ContactDomainMapper;
use App\Application\CompanyContact\Mapper\ContactJsonMapper;
use App\Application\CompanyContact\Service\ContactService;
use App\Controller\Api\ContactController;
use App\Domain\CompanyContact\CompanyContact;
use App\Shared\Exception\NotFoundException;
use App\Shared\Exception\ValidationException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class ContactControllerTest extends TestCase
{
    private ContactJsonMapper $jsonMapper;
    private ContactDomainMapper $domainMapper;
    private ContactCommandMapper $commandMapper;
    private ContactService $contactService;
    private ContactController $controller;

    protected function setUp(): void
    {
        $this->jsonMapper = new ContactJsonMapper();
        $this->domainMapper = new ContactDomainMapper();
        $this->commandMapper = new ContactCommandMapper();
        $this->contactService = $this->createMock(ContactService::class);
        $this->controller = new ContactController(
            $this->jsonMapper,
            $this->domainMapper,
            $this->commandMapper,
            $this->contactService,
        );
    }

    public function testCreateSuccess(): void
    {
        $this->contactService
            ->expects(self::once())
            ->method('create')
            ->willReturn(CompanyContact::fromDatabaseRow([
                'id' => 75,
                'company_id' => 123,
                'contact_type' => 'phone',
                'value' => '+7 (999) 123-45-67',
                'is_primary' => true,
            ]));

        $request = new Request(
            content: json_encode([
                'companyId' => 123,
                'contactType' => 'phone',
                'value' => '+7 (999) 123-45-67',
                'isPrimary' => true,
            ])
        );

        $response = $this->controller->create($request);

        self::assertSame(201, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        self::assertTrue($body['success']);
        self::assertSame('Запись контакта компании создана', $body['message']);
        self::assertSame(75, $body['data']['id']);
        self::assertSame('phone', $body['data']['contactType']);
        self::assertTrue($body['data']['isPrimary']);
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
        $this->contactService
            ->expects(self::once())
            ->method('update')
            ->willReturn(CompanyContact::fromDatabaseRow([
                'id' => 75,
                'company_id' => 123,
                'contact_type' => 'email',
                'value' => 'new@company.ru',
                'is_primary' => null,
            ]));

        $request = new Request(
            content: json_encode([
                'companyId' => 123,
                'contactType' => 'email',
                'value' => 'new@company.ru',
            ])
        );

        $response = $this->controller->update(75, $request);

        self::assertSame(200, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        self::assertTrue($body['success']);
        self::assertSame('Запись контакта компании обновлена', $body['message']);
        self::assertSame('email', $body['data']['contactType']);
        self::assertNull($body['data']['isPrimary']);
    }

    public function testUpdateNotFound(): void
    {
        $this->contactService
            ->method('update')
            ->willThrowException(new NotFoundException('Contact with id 999 not found'));

        $request = new Request(
            content: json_encode([
                'companyId' => 123,
                'contactType' => 'phone',
                'value' => '+7 (999) 123-45-67',
            ])
        );

        $this->expectException(NotFoundException::class);
        $this->controller->update(999, $request);
    }

    public function testCreateInvalidContactTypeReturns422(): void
    {
        $request = new Request(
            content: json_encode([
                'companyId' => 123,
                'contactType' => 'fax',
                'value' => '+7 (999) 123-45-67',
            ])
        );

        $this->expectException(ValidationException::class);
        $this->controller->create($request);
    }
}

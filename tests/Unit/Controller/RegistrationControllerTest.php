<?php

namespace App\Tests\Unit\Controller;

use App\Application\Registration\Mapper\RegistrationDomainMapper;
use App\Application\Registration\Mapper\RegistrationJsonMapper;
use App\Application\Registration\Service\RegistrationService;
use App\Controller\RegistrationController;
use App\Shared\Exception\ValidationException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class RegistrationControllerTest extends TestCase
{
    private RegistrationJsonMapper $jsonMapper;
    private RegistrationDomainMapper $domainMapper;
    private RegistrationService $registrationService;
    private RegistrationController $controller;

    protected function setUp(): void
    {
        $this->jsonMapper = new RegistrationJsonMapper();
        $this->domainMapper = new RegistrationDomainMapper();
        $this->registrationService = $this->createMock(RegistrationService::class);
        $this->controller = new RegistrationController(
            $this->jsonMapper,
            $this->domainMapper,
            $this->registrationService,
        );
    }

    public function testVerifySuccess(): void
    {
        $this->registrationService
            ->expects(self::once())
            ->method('complete')
            ->willReturn([
                'requestId' => '51874597-c6ce-4e25-88fd-f8e863ffceb1',
                'status' => 'ok',
            ]);

        $request = new Request(
            content: json_encode([
                'firstName' => 'Иван',
                'lastName' => 'Петров',
                'email' => 'ivan@example.com',
                'phoneNumber' => '+79152314454',
                'verificationChannelId' => 'sms',
                'patronymic' => 'Сергеевич',
            ])
        );

        $response = $this->controller->verify($request);

        self::assertSame(200, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        self::assertSame('51874597-c6ce-4e25-88fd-f8e863ffceb1', $body['requestId']);
        self::assertSame('ok', $body['status']);
    }

    public function testVerifyValidationError(): void
    {
        $request = new Request(
            content: json_encode([
                'firstName' => '',
                'lastName' => '',
                'email' => '',
                'phoneNumber' => '',
            ])
        );

        $this->expectException(ValidationException::class);
        $this->controller->verify($request);
    }

    public function testVerifyMinimalPayload(): void
    {
        $this->registrationService
            ->expects(self::once())
            ->method('complete')
            ->willReturn([
                'requestId' => 'test-uuid',
                'status' => 'ok',
            ]);

        $request = new Request(
            content: json_encode([
                'firstName' => 'Иван',
                'lastName' => 'Петров',
                'email' => 'ivan@example.com',
                'phoneNumber' => '+79152314454',
            ])
        );

        $response = $this->controller->verify($request);

        self::assertSame(200, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        self::assertSame('test-uuid', $body['requestId']);
    }
}

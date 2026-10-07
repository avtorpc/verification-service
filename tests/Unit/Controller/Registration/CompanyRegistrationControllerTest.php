<?php

namespace App\Tests\Unit\Controller\Registration;

use App\Application\Registration\Step\Service\CompanyRegistrationService;
use App\Controller\Api\CompanyRegistrationController;
use App\Shared\Exception\DuplicateException;
use App\Shared\Exception\ValidationException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class CompanyRegistrationControllerTest extends TestCase
{
    private CompanyRegistrationService $registrationService;
    private CompanyRegistrationController $controller;

    protected function setUp(): void
    {
        $this->registrationService = $this->createMock(CompanyRegistrationService::class);
        $this->controller = new CompanyRegistrationController($this->registrationService);
    }

    public function testStartReturns201(): void
    {
        $this->registrationService
            ->expects(self::once())
            ->method('start')
            ->willReturn([
                'success' => true,
                'data' => ['companyId' => 42, 'currentStep' => 'address', 'completedSteps' => ['company_info'], 'status' => 'in_progress'],
            ]);

        $request = new Request(
            content: json_encode([
                'legalEntity' => '7712345678',
                'legalFormName' => 'ООО',
                'companyName' => 'Тест',
                'countryCode' => 'RU',
                'roleCode' => 'SELLER',
            ])
        );

        $response = $this->controller->start($request);

        self::assertSame(201, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        self::assertTrue($data['success']);
        self::assertSame(42, $data['data']['companyId']);
    }

    public function testStartValidationError(): void
    {
        $this->expectException(ValidationException::class);

        $request = new Request(
            content: json_encode(['companyName' => 'Тест', 'countryCode' => 'RU'])
        );

        $this->controller->start($request);
    }

    public function testStartDuplicateReturns409(): void
    {
        $this->registrationService
            ->expects(self::once())
            ->method('start')
            ->willThrowException(new DuplicateException('Company with legalEntity "7712345678" already exists'));

        $this->expectException(DuplicateException::class);

        $request = new Request(
            content: json_encode([
                'legalEntity' => '7712345678',
                'legalFormName' => 'ООО',
                'companyName' => 'Тест',
                'countryCode' => 'RU',
            ])
        );

        $this->controller->start($request);
    }

    public function testStepAddressSuccess(): void
    {
        $this->registrationService
            ->expects(self::once())
            ->method('submitStep')
            ->willReturn([
                'success' => true,
                'data' => ['companyId' => 42, 'currentStep' => 'leader', 'completedSteps' => ['company_info', 'address'], 'status' => 'in_progress'],
            ]);

        $request = new Request(
            content: json_encode([
                'companyId' => 42,
                'addressType' => 'legal',
                'countryCode' => 'RU',
                'city' => 'Москва',
            ])
        );

        $response = $this->controller->stepAddress($request);

        self::assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        self::assertTrue($data['success']);
    }

    public function testStepMissingCompanyId(): void
    {
        $this->expectException(ValidationException::class);

        $request = new Request(content: json_encode([]));

        $this->controller->stepAddress($request);
    }

    public function testFinishesSuccessfully(): void
    {
        $this->registrationService
            ->expects(self::once())
            ->method('finish')
            ->with(42)
            ->willReturn([
                'success' => true,
                'data' => ['companyId' => 42, 'status' => 'completed'],
            ]);

        $request = new Request(content: json_encode(['companyId' => 42]));

        $response = $this->controller->finish($request);

        self::assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        self::assertTrue($data['success']);
    }

    public function testFinishMissingCompanyId(): void
    {
        $this->expectException(ValidationException::class);

        $request = new Request(content: json_encode([]));

        $this->controller->finish($request);
    }

    public function testGetProgressSuccess(): void
    {
        $this->registrationService
            ->expects(self::once())
            ->method('getProgress')
            ->with(42)
            ->willReturn([
                'success' => true,
                'data' => ['companyId' => 42, 'currentStep' => 'address', 'completedSteps' => ['company_info'], 'status' => 'in_progress'],
            ]);

        $request = new Request(query: ['companyId' => '42']);

        $response = $this->controller->getProgress($request);

        self::assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        self::assertTrue($data['success']);
        self::assertSame('address', $data['data']['currentStep']);
    }

    public function testGetProgressMissingCompanyId(): void
    {
        $this->expectException(ValidationException::class);

        $request = new Request();

        $this->controller->getProgress($request);
    }
}

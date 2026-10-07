<?php

namespace App\Tests\Unit\Application\Registration\Step\Service;

use App\Application\Registration\Serializer\CompanyRegistrationSerializer;
use App\Application\Registration\Step\Event\CompanyRegistrationEvent;
use App\Application\Registration\Step\Service\CompanyRegistrationService;
use App\Application\Shared\EventPublisherInterface;
use App\Domain\Company\Company;
use App\Domain\Registration\CompanyRegistrationProgress;
use App\Domain\Registration\Step\RegistrationStep;
use App\Infrastructure\Persistence\DbalAddressRepository;
use App\Infrastructure\Persistence\DbalBankDetailRepository;
use App\Infrastructure\Persistence\DbalCompanyRepository;
use App\Infrastructure\Persistence\DbalContactRepository;
use App\Infrastructure\Persistence\DbalLeaderRepository;
use App\Infrastructure\Persistence\Registration\DbalCompanyRegistrationProgressRepository;
use App\Shared\Exception\DuplicateException;
use App\Shared\Exception\NotFoundException;
use App\Shared\Exception\ValidationException;
use App\Shared\Time\ClockInterface;
use Doctrine\DBAL\Exception as DBALException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CompanyRegistrationServiceTest extends TestCase
{
    private DbalCompanyRepository $companyRepository;
    private DbalAddressRepository $addressRepository;
    private DbalLeaderRepository $leaderRepository;
    private DbalContactRepository $contactRepository;
    private DbalBankDetailRepository $bankDetailRepository;
    private DbalCompanyRegistrationProgressRepository $progressRepository;
    private EventDispatcherInterface $eventDispatcher;
    private ClockInterface $clock;
    private EventPublisherInterface $eventPublisher;
    private CompanyRegistrationSerializer $serializer;
    private CompanyRegistrationService $service;

    protected function setUp(): void
    {
        $this->companyRepository = $this->createMock(DbalCompanyRepository::class);
        $this->addressRepository = $this->createMock(DbalAddressRepository::class);
        $this->leaderRepository = $this->createMock(DbalLeaderRepository::class);
        $this->contactRepository = $this->createMock(DbalContactRepository::class);
        $this->bankDetailRepository = $this->createMock(DbalBankDetailRepository::class);
        $this->progressRepository = $this->createMock(DbalCompanyRegistrationProgressRepository::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->clock = $this->createMock(ClockInterface::class);
        $this->eventPublisher = $this->createMock(EventPublisherInterface::class);
        $this->serializer = new CompanyRegistrationSerializer();

        $this->clock
            ->method('now')
            ->willReturn(new \DateTimeImmutable('2026-06-24T12:00:00.000Z'));

        $this->service = new CompanyRegistrationService(
            $this->companyRepository,
            $this->addressRepository,
            $this->leaderRepository,
            $this->contactRepository,
            $this->bankDetailRepository,
            $this->progressRepository,
            $this->eventDispatcher,
            $this->clock,
            $this->eventPublisher,
            $this->serializer,
        );
    }

    public function testStartCreatesCompanyAndProgress(): void
    {
        $companyData = [
            'legal_entity' => '7712345678',
            'legal_form_name' => 'ООО',
            'company_name' => 'Тест',
            'country_code' => 'RU',
            'role_code' => 'SELLER',
            'status' => 'draft',
            'is_kz_nds_applicable' => null,
            'is_nds_payer' => null,
        ];

        $this->companyRepository
            ->expects(self::once())
            ->method('existsByLegalEntity')
            ->with('7712345678')
            ->willReturn(false);

        $this->companyRepository
            ->expects(self::once())
            ->method('insert')
            ->with($companyData)
            ->willReturn(42);

        $this->progressRepository
            ->expects(self::once())
            ->method('insert')
            ->with(42, self::isString())
            ->willReturn(1);

        $this->progressRepository
            ->expects(self::once())
            ->method('updateStep')
            ->with(1, RegistrationStep::ADDRESS, [RegistrationStep::COMPANY_INFO]);

        $this->progressRepository
            ->expects(self::once())
            ->method('findByCompanyId')
            ->with(42)
            ->willReturn(self::makeProgress(42, 'address', ['company_info']));

        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(CompanyRegistrationEvent::class));

        $result = $this->service->start($companyData);

        self::assertTrue($result['success']);
        self::assertSame(42, $result['data']['companyId']);
        self::assertSame('address', $result['data']['currentStep']);
        self::assertContains('company_info', $result['data']['completedSteps']);
        self::assertSame('in_progress', $result['data']['status']);
    }

    public function testStartThrowsOnDuplicate(): void
    {
        $this->companyRepository
            ->expects(self::once())
            ->method('existsByLegalEntity')
            ->with('7712345678')
            ->willReturn(true);

        $this->expectException(DuplicateException::class);
        $this->expectExceptionMessage('already exists');

        $this->service->start([
            'legal_entity' => '7712345678',
            'legal_form_name' => 'ООО',
            'company_name' => 'Тест',
            'country_code' => 'RU',
        ]);
    }

    public function testSubmitStepAddressAdvancesToLeader(): void
    {
        $progress = self::makeProgress(42, 'address', ['company_info']);

        $this->progressRepository
            ->expects(self::exactly(2))
            ->method('findByCompanyId')
            ->with(42)
            ->willReturnOnConsecutiveCalls($progress, self::makeProgress(42, 'leader', ['company_info', 'address']));

        $this->addressRepository
            ->expects(self::once())
            ->method('insert')
            ->with(self::callback(fn(array $d) => $d['company_id'] === 42 && $d['address_type'] === 'legal'));

        $this->progressRepository
            ->expects(self::once())
            ->method('updateStep')
            ->with(1, RegistrationStep::LEADER, [RegistrationStep::COMPANY_INFO, RegistrationStep::ADDRESS]);

        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(CompanyRegistrationEvent::class));

        $result = $this->service->submitStep(42, RegistrationStep::ADDRESS, [
            'addressType' => 'legal',
            'countryCode' => 'RU',
            'region' => 'Московская обл.',
            'city' => 'Москва',
            'street' => 'Тверская',
            'house' => '1',
            'zipCode' => '101000',
        ]);

        self::assertTrue($result['success']);
        self::assertSame('leader', $result['data']['currentStep']);
        self::assertCount(2, $result['data']['completedSteps']);
    }

    public function testSubmitStepThrowsOnMissingRequiredFields(): void
    {
        $progress = self::makeProgress(42, 'address', ['company_info']);

        $this->progressRepository
            ->expects(self::once())
            ->method('findByCompanyId')
            ->with(42)
            ->willReturn($progress);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("Field 'addressType' is required for step 'address'");

        $this->service->submitStep(42, RegistrationStep::ADDRESS, ['city' => 'Москва']);
    }

    public function testSubmitStepThrowsOnInvalidOrder(): void
    {
        $progress = self::makeProgress(42, 'address', ['company_info']);

        $this->progressRepository
            ->expects(self::once())
            ->method('findByCompanyId')
            ->with(42)
            ->willReturn($progress);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("expected 'address', got 'leader'");

        $this->service->submitStep(42, RegistrationStep::LEADER, []);
    }

    public function testSubmitStepThrowsValidationExceptionOnDbError(): void
    {
        $progress = self::makeProgress(42, 'bank_detail', ['company_info', 'address', 'leader', 'contact']);

        $this->progressRepository
            ->expects(self::once())
            ->method('findByCompanyId')
            ->with(42)
            ->willReturn($progress);

        $this->bankDetailRepository
            ->expects(self::once())
            ->method('insert')
            ->willThrowException($this->createMock(DBALException::class));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Ошибка при сохранении данных шага');

        $this->service->submitStep(42, RegistrationStep::BANK_DETAIL, [
            'accountNumber' => '40702810123456789012',
            'bankName' => 'Сбербанк',
            'bik' => '044525974',
            'countryCode' => 'RU',
        ]);
    }

    public function testSubmitStepThrowsOnNotFound(): void
    {
        $this->progressRepository
            ->expects(self::once())
            ->method('findByCompanyId')
            ->with(999)
            ->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Registration progress not found');

        $this->service->submitStep(999, RegistrationStep::ADDRESS, []);
    }

    public function testFinishCompletesRegistration(): void
    {
        $progress = self::makeProgress(42, 'bank_detail', ['company_info', 'address', 'leader', 'contact', 'bank_detail'], 'in_progress');

        $this->progressRepository
            ->expects(self::exactly(2))
            ->method('findByCompanyId')
            ->with(42)
            ->willReturnOnConsecutiveCalls($progress, self::makeProgress(42, 'bank_detail', ['company_info', 'address', 'leader', 'contact', 'bank_detail'], 'completed'));

        $company = new Company(
            id: 42,
            legalEntity: '7712345678',
            legalFormName: 'ООО',
            companyName: 'Тест',
            countryCode: 'RU',
            status: 'draft',
            roleCode: 'SELLER',
            isKzNdsApplicable: null,
            isNdsPayer: null,
            userUuid: null,
            createdAt: new \DateTimeImmutable('2026-06-18T12:00:00Z'),
            updatedAt: new \DateTimeImmutable('2026-06-18T12:00:00Z'),
        );

        $this->companyRepository
            ->expects(self::once())
            ->method('findById')
            ->with(42)
            ->willReturn($company);

        $this->companyRepository
            ->expects(self::once())
            ->method('update')
            ->with(42, self::callback(fn(array $d) => $d['status'] === 'moderation'));

        $this->progressRepository
            ->expects(self::once())
            ->method('markCompleted')
            ->with(1);

        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(CompanyRegistrationEvent::class));

        $this->eventPublisher
            ->expects(self::once())
            ->method('publish')
            ->with(self::callback(fn(array $event) => $event['event_type'] === 'company.registration.completed'));

        $result = $this->service->finish(42);

        self::assertTrue($result['success']);
        self::assertSame('completed', $result['data']['status']);
    }

    public function testFinishThrowsOnMissingSteps(): void
    {
        $progress = self::makeProgress(42, 'contact', ['company_info', 'address', 'leader']);

        $this->progressRepository
            ->expects(self::once())
            ->method('findByCompanyId')
            ->with(42)
            ->willReturn($progress);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Missing steps');

        $this->service->finish(42);
    }

    public function testGetProgressReturnsCurrentState(): void
    {
        $progress = self::makeProgress(42, 'address', ['company_info']);

        $this->progressRepository
            ->expects(self::once())
            ->method('findByCompanyId')
            ->with(42)
            ->willReturn($progress);

        $result = $this->service->getProgress(42);

        self::assertTrue($result['success']);
        self::assertSame('address', $result['data']['currentStep']);
        self::assertContains('company_info', $result['data']['completedSteps']);
    }

    public function testFullRegistrationFlow(): void
    {
        $this->companyRepository
            ->expects(self::once())
            ->method('existsByLegalEntity')
            ->with('7712345678')
            ->willReturn(false);

        $this->companyRepository
            ->expects(self::once())
            ->method('insert')
            ->willReturn(42);

        $this->progressRepository
            ->expects(self::once())
            ->method('insert')
            ->with(42, self::isString())
            ->willReturn(1);

        $this->progressRepository
            ->expects(self::once())
            ->method('updateStep')
            ->with(1, RegistrationStep::ADDRESS, [RegistrationStep::COMPANY_INFO]);

        $this->progressRepository
            ->expects(self::exactly(3))
            ->method('findByCompanyId')
            ->with(42)
            ->willReturnOnConsecutiveCalls(
                self::makeProgress(42, 'address', ['company_info']),
                self::makeProgress(42, 'bank_detail', ['company_info', 'address', 'leader', 'contact', 'bank_detail'], 'in_progress'),
                self::makeProgress(42, 'bank_detail', ['company_info', 'address', 'leader', 'contact', 'bank_detail'], 'completed'),
            );

        $company = new Company(
            id: 42, legalEntity: '7712345678', legalFormName: 'ООО',
            companyName: 'Тест', countryCode: 'RU', status: 'draft',
            roleCode: 'SELLER', isKzNdsApplicable: null, isNdsPayer: null, userUuid: null,
            createdAt: new \DateTimeImmutable('2026-06-18T12:00:00Z'),
            updatedAt: new \DateTimeImmutable('2026-06-18T12:00:00Z'),
        );

        $this->companyRepository
            ->expects(self::once())
            ->method('findById')
            ->with(42)
            ->willReturn($company);

        $this->companyRepository
            ->expects(self::once())
            ->method('update')
            ->with(42, self::callback(fn(array $d) => $d['status'] === 'moderation'));

        $this->progressRepository
            ->expects(self::once())
            ->method('markCompleted')
            ->with(1);

        $this->eventDispatcher
            ->expects(self::exactly(2))
            ->method('dispatch')
            ->with(self::isInstanceOf(CompanyRegistrationEvent::class));

        $this->eventPublisher
            ->expects(self::once())
            ->method('publish')
            ->with(self::callback(fn(array $event) => $event['event_type'] === 'company.registration.completed'));

        $this->service->start([
            'legal_entity' => '7712345678', 'legal_form_name' => 'ООО',
            'company_name' => 'Тест', 'country_code' => 'RU',
        ]);

        $result = $this->service->finish(42);

        self::assertTrue($result['success']);
    }

    private static function makeProgress(int $companyId, string $currentStep, array $completedSteps, string $status = 'in_progress'): CompanyRegistrationProgress
    {
        return CompanyRegistrationProgress::fromDatabaseRow([
            'id' => 1,
            'company_id' => $companyId,
            'current_step' => $currentStep,
            'completed_steps' => json_encode($completedSteps),
            'status' => $status,
            'trace_id' => '51874597-c6ce-4e25-88fd-f8e863ffceb1',
            'created_at' => '2026-06-18T12:00:00Z',
            'updated_at' => '2026-06-18T12:00:00Z',
        ]);
    }
}

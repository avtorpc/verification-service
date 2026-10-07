<?php

namespace App\Application\Registration\Step\Service;

use App\Application\Registration\Event\CompanyRegistrationCompletedEvent;
use App\Application\Registration\Serializer\CompanyRegistrationSerializer;
use App\Application\Registration\Step\Event\CompanyRegistrationEvent;
use App\Application\Shared\EventPublisherInterface;
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
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CompanyRegistrationService
{
    private const STEP_VALIDATION = [
        'address' => ['addressType', 'countryCode', 'city', 'street', 'house', 'zipCode'],
        'leader' => ['firstName', 'lastName', 'documentTypeCode'],
        'contact' => ['email', 'phone'],
        'bank_detail' => ['accountNumber', 'bankName', 'bik', 'countryCode'],
    ];

    public function __construct(
        private DbalCompanyRepository $companyRepository,
        private DbalAddressRepository $addressRepository,
        private DbalLeaderRepository $leaderRepository,
        private DbalContactRepository $contactRepository,
        private DbalBankDetailRepository $bankDetailRepository,
        private DbalCompanyRegistrationProgressRepository $progressRepository,
        private EventDispatcherInterface $eventDispatcher,
        private ClockInterface $clock,
        private EventPublisherInterface $eventPublisher,
        private CompanyRegistrationSerializer $serializer,
        private string $serviceName = 'mp-core',
    ) {
    }

    public function start(array $companyData): array
    {
        if ($this->companyRepository->existsByLegalEntity($companyData['legal_entity'])) {
            throw new DuplicateException(
                sprintf('Company with legalEntity "%s" already exists', $companyData['legal_entity'])
            );
        }

        $traceId = $companyData['trace_id'] ?? $this->generateUuidV4();
        unset($companyData['trace_id']);

        $companyId = $this->companyRepository->insert($companyData);
        $progressId = $this->progressRepository->insert($companyId, $traceId);

        $this->progressRepository->updateStep(
            $progressId,
            RegistrationStep::ADDRESS,
            [RegistrationStep::COMPANY_INFO]
        );

        $progress = $this->progressRepository->findByCompanyId($companyId);

        $this->eventDispatcher->dispatch(new CompanyRegistrationEvent(
            type: 'company.registration.started',
            companyId: $companyId,
            data: ['step' => RegistrationStep::COMPANY_INFO->value],
        ));

        return $this->buildResponse($companyId, $progress, 'Регистрация компании начата');
    }

    public function submitStep(int $companyId, RegistrationStep $step, array $data): array
    {
        $progress = $this->progressRepository->findByCompanyId($companyId);
        if ($progress === null) {
            throw new NotFoundException("Registration progress not found for company {$companyId}");
        }

        if ($progress->currentStep->value !== $step->value) {
            throw new ValidationException(
                "Invalid step order: expected '{$progress->currentStep->value}', got '{$step->value}'"
            );
        }

        $this->validateStepData($step, $data);
        $this->persistStepData($companyId, $step, $data);

        $completedSteps = $progress->completedSteps;
        $completedSteps[] = $step;

        $nextStep = $step->next();

        if ($nextStep !== null) {
            $this->progressRepository->updateStep($progress->id, $nextStep, $completedSteps);
        } else {
            $this->progressRepository->updateStep($progress->id, $step, $completedSteps);
        }

        $this->eventDispatcher->dispatch(new CompanyRegistrationEvent(
            type: 'company.registration.step_completed',
            companyId: $companyId,
            data: [
                'step' => $step->value,
                'nextStep' => $nextStep?->value,
            ],
        ));

        $updatedProgress = $this->progressRepository->findByCompanyId($companyId);

        return $this->buildResponse($companyId, $updatedProgress, "Шаг '{$step->label()}' сохранён");
    }

    public function updateStep(int $companyId, RegistrationStep $step, array $data): array
    {
        $progress = $this->progressRepository->findByCompanyId($companyId);
        if ($progress === null) {
            throw new NotFoundException("Registration progress not found for company {$companyId}");
        }

        $this->validateStepData($step, $data);
        $this->replaceStepData($companyId, $step, $data);

        $updatedProgress = $this->progressRepository->findByCompanyId($companyId);

        return $this->buildResponse($companyId, $updatedProgress, "Данные шага '{$step->label()}' обновлены");
    }

    public function finish(int $companyId): array
    {
        $progress = $this->progressRepository->findByCompanyId($companyId);
        if ($progress === null) {
            throw new NotFoundException("Registration progress not found for company {$companyId}");
        }

        $allSteps = RegistrationStep::orderedSteps();
        $missingSteps = array_udiff($allSteps, $progress->completedSteps, fn($a, $b) => $a->value <=> $b->value);

        if (!empty($missingSteps)) {
            $missingLabels = array_map(fn(RegistrationStep $s) => $s->label(), $missingSteps);
            throw new ValidationException(
                'Cannot finish registration. Missing steps: ' . implode(', ', $missingLabels)
            );
        }

        $company = $this->companyRepository->findById($companyId);
        if ($company === null) {
            throw new NotFoundException("Company {$companyId} not found");
        }

        $this->companyRepository->update($companyId, [
            'legal_entity' => $company->legalEntity,
            'legal_form_name' => $company->legalFormName,
            'company_name' => $company->companyName,
            'role_code' => $company->roleCode,
            'country_code' => $company->countryCode,
            'status' => 'moderation',
            'is_kz_nds_applicable' => $company->isKzNdsApplicable,
            'is_nds_payer' => $company->isNdsPayer,
        ]);
        $this->progressRepository->markCompleted($progress->id);

        $this->eventDispatcher->dispatch(new CompanyRegistrationEvent(
            type: 'company.registration.completed',
            companyId: $companyId,
            data: ['status' => 'moderation'],
        ));

        try {
            $this->publishCompanyRegistrationCompleted($company, $progress);
        } catch (\Throwable $e) {
            // Kafka может быть недоступен — не роняем finish
        }

        $updatedProgress = $this->progressRepository->findByCompanyId($companyId);

        return $this->buildResponse($companyId, $updatedProgress, 'Регистрация компании завершена');
    }

    public function getProgress(int $companyId): array
    {
        $progress = $this->progressRepository->findByCompanyId($companyId);
        if ($progress === null) {
            throw new NotFoundException("Registration progress not found for company {$companyId}");
        }

        return $this->buildResponse($companyId, $progress, 'Текущий прогресс регистрации');
    }

    private function publishCompanyRegistrationCompleted(\App\Domain\Company\Company $company, CompanyRegistrationProgress $progress): void
    {
        $now = $this->clock->now()->format('Y-m-d\TH:i:s.v\Z');
        $traceId = $progress->traceId ?? $this->generateUuidV4();

        $event = new CompanyRegistrationCompletedEvent(
            eventId: $this->generateUuidV4(),
            eventType: 'company.registration.completed',
            eventVersion: 1,
            occurredAt: $now,
            traceId: $traceId,
            correlationId: $traceId,
            service: $this->serviceName,
            companyId: $company->id,
            legalEntity: $company->legalEntity,
            companyName: $company->companyName,
            legalFormName: $company->legalFormName,
            roleCode: $company->roleCode,
            countryCode: $company->countryCode,
            status: 'moderation',
        );

        $this->eventPublisher->publish($this->serializer->toArray($event));
    }

    private function validateStepData(RegistrationStep $step, array $data): void
    {
        $requiredFields = self::STEP_VALIDATION[$step->value] ?? [];

        foreach ($requiredFields as $field) {
            $value = $data[$field] ?? null;
            if ($value === null || (is_string($value) && trim($value) === '')) {
                throw new ValidationException(
                    "Field '{$field}' is required for step '{$step->value}'"
                );
            }
        }
    }

    private function generateUuidV4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    private function persistStepData(int $companyId, RegistrationStep $step, array $data): void
    {
        $snaked = [];
        foreach ($data as $key => $value) {
            $snaked[strtolower((string) preg_replace('/([A-Z])/', '_$1', lcfirst((string) $key)))] = $value;
        }

        try {
            switch ($step) {
                case RegistrationStep::COMPANY_INFO:
                    $this->companyRepository->update($companyId, $snaked);
                    break;
                case RegistrationStep::ADDRESS:
                    $this->addressRepository->insert(array_merge($snaked, ['company_id' => $companyId]));
                    break;
                case RegistrationStep::LEADER:
                    $this->leaderRepository->insert(array_merge($snaked, ['company_id' => $companyId]));
                    break;
                case RegistrationStep::CONTACT:
                    $this->contactRepository->insert(array_merge(
                        ['contact_type' => 'email', 'value' => $snaked['email'] ?? ''],
                        ['company_id' => $companyId]
                    ));
                    if (!empty($snaked['phone'])) {
                        $this->contactRepository->insert(array_merge(
                            ['contact_type' => 'phone', 'value' => $snaked['phone']],
                            ['company_id' => $companyId]
                        ));
                    }
                    break;
                case RegistrationStep::BANK_DETAIL:
                    $this->bankDetailRepository->insert(array_merge($snaked, ['company_id' => $companyId]));
                    break;
            }
        } catch (DBALException $e) {
            throw new ValidationException(
                "Ошибка при сохранении данных шага '{$step->label()}'. Проверьте правильность заполнения полей."
            );
        }
    }

    private function replaceStepData(int $companyId, RegistrationStep $step, array $data): void
    {
        $snaked = [];
        foreach ($data as $key => $value) {
            $snaked[strtolower((string) preg_replace('/([A-Z])/', '_$1', lcfirst((string) $key)))] = $value;
        }

        try {
            switch ($step) {
                case RegistrationStep::ADDRESS:
                    $this->addressRepository->deleteByCompanyId($companyId);
                    $this->addressRepository->insert(array_merge($snaked, ['company_id' => $companyId]));
                    break;
                case RegistrationStep::LEADER:
                    $this->leaderRepository->deleteByCompanyId($companyId);
                    $this->leaderRepository->insert(array_merge($snaked, ['company_id' => $companyId]));
                    break;
                case RegistrationStep::CONTACT:
                    $this->contactRepository->deleteByCompanyId($companyId);
                    $this->contactRepository->insert(array_merge(
                        ['contact_type' => 'email', 'value' => $snaked['email'] ?? ''],
                        ['company_id' => $companyId]
                    ));
                    if (!empty($snaked['phone'])) {
                        $this->contactRepository->insert(array_merge(
                            ['contact_type' => 'phone', 'value' => $snaked['phone']],
                            ['company_id' => $companyId]
                        ));
                    }
                    break;
                case RegistrationStep::BANK_DETAIL:
                    $this->bankDetailRepository->deleteByCompanyId($companyId);
                    $this->bankDetailRepository->insert(array_merge($snaked, ['company_id' => $companyId]));
                    break;
                default:
                    throw new ValidationException("Шаг '{$step->label()}' не поддерживает обновление");
            }
        } catch (DBALException $e) {
            throw new ValidationException(
                "Ошибка при обновлении данных шага '{$step->label()}'. Проверьте правильность заполнения полей."
            );
        }
    }

    private function buildResponse(int $companyId, ?CompanyRegistrationProgress $progress, string $message): array
    {
        return [
            'success' => true,
            'timestamp' => (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d\TH:i:s.v\Z'),
            'message' => $message,
            'expiresInSeconds' => 600,
            'data' => [
                'companyId' => $companyId,
                'currentStep' => $progress?->currentStep->value,
                'completedSteps' => $progress !== null ? array_map(fn(RegistrationStep $s) => $s->value, $progress->completedSteps) : [],
                'status' => $progress?->status,
            ],
        ];
    }
}

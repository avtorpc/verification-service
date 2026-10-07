<?php

declare(strict_types=1);

namespace App\Application\Registration\VerificationPHONE;

use App\Application\Registration\VerificationPHONE\Command\VerificationCodeCommand;
use App\Domain\Dictionaries\DictionaryDomainService;
use App\Infrastructure\Registration\VerificationUsersStorage;
use App\Shared\Utils\VerificationCodeGenerator;

final class VerificationPhoneService
{
    public function __construct(
        private VerificationUsersStorage $storage,
        private VerificationCodeGenerator $codeGenerator,
        private DictionaryDomainService $dictionaryDomainService
    ) {}

    /**
     * Проверяет наличие активной (неподтвержденной) SMS-верификации.
     *
     * Нельзя отправлять новый код, пока предыдущая верификация
     * ещё активна в пределах TTL.
     */
    public function hasActiveSmsVerification(
        string $phoneNumber,
        int $ttl
    ): bool {
        return $this->storage->hasActiveSmsVerification(
            $phoneNumber,
            $ttl
        );
    }

    /**
     * Возвращает количество запросов с одного IP
     * за заданный промежуток времени.
     */
    public function countRecentByIp(
        string $ip,
        int $seconds
    ): int {
        return $this->storage->countRecentByIp(
            $ip,
            $seconds
        );
    }

    /**
     * Создание пользователя + генерация кода.
     */
    public function createVerificationWithCode(
        VerificationCodeCommand $command,
        array $registration,
        int $maxAttempts
    ): string {

        $code = $this->codeGenerator->generate();

        $this->storage->createWithCode(
            requestId: $command->requestId,
            legalEntity: $command->legalEntity,
            companyId: null,
            roleId: $registration['role_code'],
            countryId: $command->countryAlpha2,
            email: $command->email,
            lastName: $command->lastName,
            firstName: $command->firstName,
            patronymic: $command->patronymic,
            phoneNumber: $command->phoneNumber,
            verificationChannelId: $command->channelCode,
            verificationCode: $code,
            verificationAttempts: $maxAttempts
        );

        return $code;
    }

    /**
     * Сохраняет новый код.
     */
    public function saveCode(
        string $requestId,
        string $code
    ): void {
        $this->storage->saveCode(
            $requestId,
            $code
        );
    }

    /**
     * Увеличивает количество попыток проверки.
     */
    public function incrementAttempts(
        string $requestId
    ): void {
        $this->storage->incrementAttempts(
            $requestId
        );
    }

    /**
     * Отмечает пользователя как успешно подтвержденного.
     */
    public function markVerified(
        string $requestId
    ): void {
        $this->storage->markVerified(
            $requestId
        );
    }

    /**
     * Обновляет время отправки SMS.
     */
    public function updateSendTime(
        string $requestId
    ): void {
        $this->storage->updateSendTime(
            $requestId
        );
    }
}

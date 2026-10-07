<?php

namespace App\Application\Registration\QuickSignup;

use App\Domain\Dictionaries\DictionaryDomainService;
use App\Infrastructure\Registration\SignupRequestStorage;
use App\Shared\Utils\VerificationCodeGenerator;

class SignupRequestService
{
    public function __construct(
        private SignupRequestStorage $storage,
        private VerificationCodeGenerator $codeGenerator,
        private DictionaryDomainService $dictionaryDomainservice
    ) {}

    /**
     * Проверяет, создавал ли пользователь заявку на регистрацию
     * с данным email в течение последних N секунд.
     *
     * Используется для ограничения частоты запросов (rate limit).
     */
    public function hasRecentSignupByEmail(string $email, int $seconds): bool
    {
        return $this->storage->hasRecentSignupByEmail($email, $seconds);
    }

    /**
     * Проверяет наличие незавершённой (неподтверждённой) регистраци по email.
     *
     * Нельзя создавать новую заявку, если предыдущая ещё активна
     * в пределах TTL (времени жизни).
     */
    public function hasActiveSignup(string $email, int $ttl): bool
    {
        return $this->storage->hasActiveSignup($email, $ttl);
    }

    /**
     * Возвращает количество регистраций с одного IP
     * за заданный промежуток времени.
     *
     * Используется для защиты от массовых регистраций (анти-абьюз).
     */
    public function countRecentByIp(string $ip, int $seconds): int
    {
        return $this->storage->countRecentByIp($ip, $seconds);
    }

    /**
     * Создание заявки + генерация кода (атомарная операция)
     */
    public function createSignupWithCode(
        string $requestId,
        string $email,
        string $roleCode,
        string $country,
        bool $acceptTerms,
        ?bool $acceptMarketing,
        ?string $acceptMarketingVer,
        ?string $urlPageCheckout,
        ?string $userAgent,
        ?string $ip,
        int $verification_attempts
    ): string {
        $statusCode = $this->dictionaryDomainservice->getVerificationStatus('new_request');
        $code = $this->codeGenerator->generate();

        // один вызов в storage (как мы делали через WITH)
        $this->storage->createWithCode(
            $requestId,
            $email,
            $roleCode,
            $country,
            $statusCode,
            $code,
            $verification_attempts,
            $acceptTerms,
            $acceptMarketing,
            $acceptMarketingVer,
            $urlPageCheckout,
            $userAgent,
            $ip
        );

        return $code;
    }

    /**
     * Сохраняет код верификации для заявки.
     *
     * Обычно вызывается после генерации и отправки кода пользователю.
     */
    public function saveCode(string $requestId, string $code): void
    {
        $this->storage->saveCode($requestId, $code);
    }

    /**
     * Отмечает заявку как успешно подтверждённую (email verified).
     *
     * Используется после успешной проверки кода.
     */
    public function markVerified(string $requestId): void
    {
        $this->storage->markVerified($requestId);
    }


    public function remainingLifetime(string $requestId): int
    {
        $registration = $this->storage->findByRequestId($requestId);
        if (!$registration) {
            throw new \RuntimeException('Registration not found');
        }
        return max(0, (new \DateTimeImmutable($registration['expires_at']))->getTimestamp() - time());
    }

    public function updateSendTime( string $requestId ): void{
        $this->storage->updateSendTime($requestId);
    }
}

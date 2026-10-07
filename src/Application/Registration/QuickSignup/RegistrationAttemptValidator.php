<?php

declare(strict_types=1);

namespace App\Application\Registration\QuickSignup;

use App\Application\Registration\QuickSignup\DTO\RequestContext;
use App\Domain\Settings\AppSettingsService;
use App\Infrastructure\RateLimit\EmailRateLimiter;
use App\Shared\Exception\ErrorCode;
use App\Shared\Exception\TooManyRequestsException;
use App\Api\Dictionaries\DictionariesApiInterface;
use App\Api\Dictionaries\DTO\ApiEnvelope;
use App\Api\Dictionaries\DTO\RolesCountriesItem;
use App\Api\Dictionaries\DTO\SettingsItem;

class RegistrationAttemptValidator
{
    public function __construct(
        private SignupRequestService $signupRequestService,
        private EmailRateLimiter $emailLimiter,
        private AppSettingsService $appSettingsService
    ) {}

    /**
     *
     * Главная точка валидации попытки регистрации
     *
     * @param string $email
     * @param string|null $ip
     * @return void
     *
     * @throws TooManyRequestsException
     */
    public function validateSignupAttempt(string $email, RequestContext $context): void
    {
        $this->assertEmailRateLimitNotExceeded($email, $context);
        $this->assertNoActiveSignup($email, $context);
        $this->assertIpRateLimitNotExceeded($context);
    }

    /**
     * Проверяет и УСТАНАВЛИВАЕТ email cooldown (Redis)
     */
    private function assertEmailRateLimitNotExceeded(
        string $email,
        RequestContext $context
    ): void {
        $emailCooldown = $this->appSettingsService->getEmailCooldown();

        $acquired = $this->emailLimiter->tryAcquire($email, $emailCooldown);

        if ($acquired) {
            return;
        }

        throw new TooManyRequestsException(
            'Слишком частые попытки регистрации. Попробуйте позже.',
            ErrorCode::B_TOO_MANY_REQUESTS,
            [
                'type' => 'email_registration_rate_limit_exceeded',
                'email' => $email,
                'cooldown_seconds' => $emailCooldown,
                'requestId' => $email,
                'request' => [
                    'ip' => $context->ip,
                    'uri' => $context->uri,
                    'method' => $context->method,
                    'user_agent' => $context->userAgent
                ],
            ]
        );
    }

    /**
     * Проверяет наличие незавершённой регистрации по email
     *
     * @param string $email
     * @return void
     */
    private function assertNoActiveSignup(string $email, RequestContext $context): void
    {
        $ttl = $this->appSettingsService->getSignupTtl();

        if (!$this->signupRequestService->hasActiveSignup($email, $ttl)) {
            return;
        }

        throw new TooManyRequestsException(
            'Ваша заявка на регистрацию с этим адресом почты уже в обработке.',
            ErrorCode::VERIFICATION_EMAIL_PROCESS_ALREADY_STARTED,
            [
                'type' => 'active_signup_exists',
                'email' => $email,
                'ttl_seconds' => $ttl,
                'requestId' => $email,
                'request' => [
                    'ip' => $context->ip,
                    'uri' => $context->uri,
                    'method' => $context->method,
                    'user_agent' => $context->userAgent,
                ],
            ]
        );
    }

    /**
     * Проверяет rate limit по IP
     *
     * @param string|null $ip
     * @return void
     */
    private function assertIpRateLimitNotExceeded($context): void
    {
        if ($context->ip === null) {
            return;
        }

        $window = $this->appSettingsService->getIpWindow();
        $maxAttempts = $this->appSettingsService->getIpMaxAttempts();

        $attempts = $this->signupRequestService->countRecentByIp($context->ip, $window);

        if ($attempts < $maxAttempts) {
            return;
        }

        throw new TooManyRequestsException(
            'Слишком много регистраций с этого IP. Попробуйте позже.',
            ErrorCode::B_TOO_MANY_REQUESTS,
            [
                'type' => 'ip_registration_rate_limit_exceeded',
                'ip' => $context->ip,
                'window_seconds' => $window,
                'max_attempts' => $maxAttempts,
                'attempts' => $attempts,
                'request' => [
                    'ip' => $context->ip,
                    'uri' => $context->uri,
                    'method' => $context->method,
                    'user_agent' => $context->userAgent,
                ],
            ]
        );
    }
}

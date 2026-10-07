<?php

declare(strict_types=1);

namespace App\Application\Registration\VerificationPHONE;

use App\Application\Registration\QuickSignup\DTO\RequestContext;
use App\Domain\Settings\AppSettingsService;
use App\Infrastructure\RateLimit\PhoneRateLimiter;
use App\Infrastructure\RateLimit\SmsRateLimiter;
use App\Shared\Exception\ErrorCode;
use App\Shared\Exception\TooManyRequestsException;

final class SmsCodeSendAttemptValidator
{
    public function __construct(
        private SmsRateLimiter $phoneLimiter,
        private VerificationPhoneService $verificationRequestService,
        private AppSettingsService $appSettingsService,
    ) {
    }

    /**
     * Главная точка валидации отправки SMS-кода
     */
    public function validateSendAttempt(
        string $phoneNumber,
        RequestContext $context
    ): void {
        $this->assertPhoneRateLimitNotExceeded($phoneNumber, $context);
        $this->assertNoActiveSmsVerification($phoneNumber, $context);
       // $this->assertIpRateLimitNotExceeded($context);
    }

    /**
     * Rate limit по телефону (Redis cooldown)
     */
    private function assertPhoneRateLimitNotExceeded(
        string $phoneNumber,
        RequestContext $context
    ): void {
        $cooldown = $this->appSettingsService->getSmsCooldown();

        $acquired = $this->phoneLimiter->tryAcquire($phoneNumber, $cooldown);

        if ($acquired) {
            return;
        }

        throw new TooManyRequestsException(
            'Слишком частые запросы SMS-кода.',
            ErrorCode::B_TOO_MANY_REQUESTS,
            [
                'type' => 'sms_rate_limit_exceeded',
                'phoneNumber' => $phoneNumber,
                'cooldown_seconds' => $cooldown,
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
     * Проверка: уже есть активная SMS-верификация
     */
    private function assertNoActiveSmsVerification(
        string $phoneNumber,
        RequestContext $context
    ): void {
        $ttl = $this->appSettingsService->getSmsVerificationTtl();

        if (
            !$this->verificationRequestService
                ->hasActiveSmsVerification($phoneNumber, $ttl)
        ) {
            return;
        }

        throw new TooManyRequestsException(
            'SMS-код уже был отправлен. Попробуйте позже.',
            ErrorCode::B_TOO_MANY_REQUESTS,
            [
                'type' => 'active_sms_verification_exists',
                'phoneNumber' => $phoneNumber,
                'ttl_seconds' => $ttl,
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
     * IP rate limit (можно позже вынести в общий сервис)
     */
//    private function assertIpRateLimitNotExceeded(RequestContext $context): void
//    {
//        if ($context->ip === null) {
//            return;
//        }
//
//        $window = $this->appSettingsService->getIpWindow();
//        $maxAttempts = $this->appSettingsService->getIpMaxAttempts();
//
//        $attempts = $this->verificationRequestService
//            ->countRecentByIp($context->ip, $window);
//
//        if ($attempts < $maxAttempts) {
//            return;
//        }
//
//        throw new TooManyRequestsException(
//            'Слишком много SMS-запросов с этого IP.',
//            ErrorCode::B_TOO_MANY_REQUESTS,
//            [
//                'type' => 'ip_sms_rate_limit_exceeded',
//                'ip' => $context->ip,
//                'window_seconds' => $window,
//                'max_attempts' => $maxAttempts,
//                'attempts' => $attempts,
//                'request' => [
//                    'ip' => $context->ip,
//                    'uri' => $context->uri,
//                    'method' => $context->method,
//                    'user_agent' => $context->userAgent,
//                ],
//            ]
//        );
//    }
}

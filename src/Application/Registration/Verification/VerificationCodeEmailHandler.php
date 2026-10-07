<?php

namespace App\Application\Registration\Verification;

use App\Application\Registration\Verification\Command\VerificationCodeCommand;
use App\Application\Registration\Verification\DTO\VerificationCodeResponse;
use App\Domain\Settings\AppSettingsService;
use Psr\Log\LoggerInterface;
use App\Infrastructure\Registration\Otp\Redis\RedisOtpStorage;

final class VerificationCodeEmailHandler
{
    public function __construct(
        private RedisOtpStorage $otpStorage,
        private VerificationCodeMailValidator $validator,
        private VerificationCodeEmailService  $service,
        private LoggerInterface               $logger,
        private AppSettingsService            $appSettingsService
    ) {}

    public function handle(VerificationCodeCommand $command): VerificationCodeResponse
    {
        $this->logger->info('Verification started', [
            'requestId' => $command->requestId,
        ]);

        /*
        |--------------------------------------------------------------------------
        | 1. VALIDATION бизнес правила
        |--------------------------------------------------------------------------
        */

        $this->validator->validate($command);

        /*
        |--------------------------------------------------------------------------
        | 2. BUSINESS LOGIC - проверка кода с email и подсчет попыток его ввода
        |--------------------------------------------------------------------------
        */

        $result = $this->service->verify(
            $command->requestId,
            $command->verificationCode,
            $this->appSettingsService->getMaxVerificationAttempts()
        );

        $this->logger->info('Verification finished', [
            'requestId' => $command->requestId,
            'success' => $result['success'],
        ]);

        /*
        |--------------------------------------------------------------------------
        | 3. RESPONSE
        |--------------------------------------------------------------------------
        */

        return new VerificationCodeResponse(
            requestId: $command->requestId,
            expiresInSeconds: $this->otpStorage->remainingSeconds($command->requestId),
            verificationAttemptsLeft: $result['attemptsLeft'],
            success: $result['success'],
            message: $result['message'],
            errorCode: $result['errorCode'] ?? null
        );
    }
}

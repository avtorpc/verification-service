<?php

namespace App\Application\Registration\ResendSmsCode;

use App\Application\Registration\ResendSmsCode\Command\ResendSmsCodeCommand;
use App\Application\Registration\ResendSmsCode\DTO\ResendSmsCodeResponse;
use App\Domain\Event\SmsVerificationEventFactory;
use App\Domain\Settings\AppSettingsService;
use App\Infrastructure\EventPublisher\HttpSmsEventPublisher;
use App\Infrastructure\Registration\Otp\Redis\RedisOtpStorage;
use App\Infrastructure\Registration\VerificationUsersStorage;
use App\Shared\Exception\BadRequestException;
use App\Shared\Exception\ErrorCode;
use App\Shared\Time\ClockInterface;
use App\Shared\Utils\VerificationCodeGenerator;
use Psr\Log\LoggerInterface;

final class ResendSmsCodeHandler
{
    public function __construct(
        private VerificationUsersStorage $storage,
        private RedisOtpStorage $otpStorage,
        private AppSettingsService $appSettingsService,
        private VerificationCodeGenerator $codeGenerator,
        private SmsVerificationEventFactory $eventFactory,
        private HttpSmsEventPublisher $publisher,
        private ClockInterface $clock,
        private LoggerInterface $logger
    ) {}

    public function handle(ResendSmsCodeCommand $command): ResendSmsCodeResponse
    {
        $this->logger->info('SMS resend started', [
            'requestId' => $command->requestId,
            'ip' => $command->ip,
            'userAgent' => $command->userAgent,
        ]);

        $registration = $this->storage->findByRequestId($command->requestId);

        if ($registration === null) {
            throw new BadRequestException(
                'Регистрация не найдена',
                ErrorCode::B_REG_NOT_FOUND,
                ['requestId' => $command->requestId]
            );
        }

        if ($this->isVerified($registration['is_verified'] ?? false)) {
            throw new BadRequestException(
                'Телефон уже подтвержден',
                ErrorCode::B_VALIDATION_FAILED,
                ['requestId' => $command->requestId]
            );
        }

        $ttl = $this->appSettingsService->getSmsVerificationTtl();
        $maxAttempts = $this->appSettingsService->getMaxVerificationAttempts();

        $this->assertRegistrationNotExpired($registration, $command->requestId, $ttl);
        $this->assertResendAllowed($command->requestId);

        $phoneNumber = (string) ($registration['phone_number'] ?? '');

        if ($phoneNumber === '') {
            throw new BadRequestException(
                'В регистрационных данных отсутствует номер телефона',
                ErrorCode::B_PHONE_INVALID,
                ['requestId' => $command->requestId]
            );
        }

        $code = $this->codeGenerator->generate();

        $this->storage->saveCode($command->requestId, $code);

        $event = $this->eventFactory->create(
            $command->requestId,
            $phoneNumber,
            $code
        );

        $this->publisher->publish($event);

        $this->otpStorage->saveOtp(
            $command->requestId,
            $code,
            $ttl,
            $maxAttempts
        );

        $this->storage->updateVerificationAttemptsLeft(
            $command->requestId,
            $maxAttempts
        );
        $this->storage->updateSendTime($command->requestId);

        $this->logger->info('SMS resend finished', [
            'requestId' => $command->requestId,
        ]);

        return new ResendSmsCodeResponse(
            requestId: $command->requestId,
            expiresInSeconds: $ttl,
            verificationAttemptsLeft: $maxAttempts,
            clock: $this->clock
        );
    }

    private function assertRegistrationNotExpired(
        array $registration,
        string $requestId,
        int $ttl
    ): void {
        $updatedAt = strtotime((string) ($registration['updated_at'] ?? ''));

        if ($updatedAt === false || (time() - $updatedAt) > $ttl) {
            throw new BadRequestException(
                'Время регистрации истекло. Начните заново',
                ErrorCode::B_REG_EXPIRED,
                ['requestId' => $requestId]
            );
        }
    }

    private function isVerified(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return in_array(strtolower($value), ['1', 'true', 't'], true);
        }

        return $value === 1;
    }

    private function assertResendAllowed(string $requestId): void
    {
        $otp = $this->otpStorage->getOtp($requestId);

        if ($otp === null) {
            return;
        }

        if ($otp['attempts'] >= $otp['max_attempts']) {
            return;
        }

        throw new BadRequestException(
            'Повторная отправка SMS доступна после исчерпания попыток ввода кода',
            ErrorCode::B_VALIDATION_FAILED,
            [
                'requestId' => $requestId,
                'attempts' => $otp['attempts'],
                'maxAttempts' => $otp['max_attempts'],
            ]
        );
    }
}

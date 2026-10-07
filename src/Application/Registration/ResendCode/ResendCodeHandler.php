<?php

namespace App\Application\Registration\ResendCode;

use App\Application\Registration\ResendCode\Command\ResendCodeCommand;
use App\Application\Registration\ResendCode\DTO\ResendCodeResponse;
use App\Domain\Settings\AppSettingsService;
use App\Infrastructure\Registration\SignupRequestStorage;
use App\Infrastructure\Registration\Otp\Redis\RedisOtpStorage;
use App\Domain\Event\EmailVerificationEventFactory;
use App\Infrastructure\EventPublisher\EmailEventPublisherInterface;
use App\Shared\Time\ClockInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

final class ResendCodeHandler
{
    public function __construct(
        private SignupRequestStorage $signupStorage,
        private RedisOtpStorage $otpStorage,
        private AppSettingsService $appSettingsService,
        private EmailVerificationEventFactory $eventFactory,
        private EmailEventPublisherInterface $publisher,
        private ClockInterface $clock,
    ) {}

    public function handle(ResendCodeCommand $command): ResendCodeResponse
    {
        $registration = $this->signupStorage->findByRequestId($command->requestId);
        if (!$registration) {
            throw new NotFoundHttpException('Регистрация не найдена');
        }
        if ($registration['is_verified']) {
            throw new BadRequestHttpException('Email уже подтверждён; повторная отправка не требуется');
        }
        if (new \DateTimeImmutable($registration['expires_at']) <= $this->clock->now()) {
            throw new BadRequestHttpException('Время регистрации истекло. Начните заново');
        }

        $maximum = $this->appSettingsService->getMaxCodeRegenerations();
        $interval = $this->appSettingsService->getResendIntervalSeconds();
        $codeTtl = $this->appSettingsService->getCodeExpirySeconds();
        $attempts = $this->appSettingsService->getMaxVerificationAttempts();
        if ((int) $registration['resend_attempts'] >= $maximum) {
            throw new TooManyRequestsHttpException(null, 'Лимит повторных отправок исчерпан');
        }

        // One SQL update enforces both limits even for concurrent requests.
        $reserved = $this->signupStorage->reserveEmailResend($command->requestId, $maximum, $interval);
        if (!$reserved) {
            throw new TooManyRequestsHttpException($interval, 'Повторная отправка пока недоступна. Обновите состояние регистрации');
        }
        $ttl = min($codeTtl, (int) $reserved['remaining_seconds']);
        $previous = $this->otpStorage->getOtp($command->requestId);
        do {
            $code = (string) random_int(100000, 999999);
        } while ($previous && hash_equals($previous['code_hash'], hash('sha256', $code)));

        // Reserve remains consumed on delivery failure: a new code already superseded the old one.
        $this->otpStorage->saveOtp($command->requestId, $code, $ttl, $attempts);
        $this->signupStorage->updateVerificationAttemptsLeft($command->requestId, $attempts);
        $left = $maximum - (int) $reserved['resend_attempts'];
        $event = $this->eventFactory->create($command->requestId, $reserved['email'], $code, $ttl, $left);
        $this->publisher->publish($event);

        return new ResendCodeResponse($command->requestId, $ttl, $left, $this->clock);
    }
}

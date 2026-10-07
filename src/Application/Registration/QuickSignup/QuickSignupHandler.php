<?php

namespace App\Application\Registration\QuickSignup;

use App\Application\Registration\QuickSignup\Command\QuickSignupCommand;
use App\Application\Registration\QuickSignup\DTO\QuickSignupResponse;
use App\Application\Registration\QuickSignup\DTO\RequestContext;
use App\Domain\Settings\AppSettingsService;
use App\Infrastructure\Registration\Otp\Redis\RedisOtpStorage;
use App\Shared\Time\ClockInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use App\Domain\Event\EmailVerificationEventFactory;
use App\Infrastructure\EventPublisher\EmailEventPublisherInterface;

/**
 * Класс для обработки поступивших данных с первого шага верификации
 * Проверка роли-страны
 * Полученных чеков о Маркетинге и Персональных данных
 * Отпрака проверочного кода на mail
 */
class QuickSignupHandler
{
    private int $ttl; // Сколько секунд храним в REdis код регистрации
    private int $maxAttempts; // Количество попыток ввода кода до его повторения

    public function __construct(
        private QuickSignupValidator $validator,
        private SignupRequestService $signupService,
        private RedisOtpStorage $redis,
        private RegistrationAttemptValidator $registrationAttemptValidator,
        private LoggerInterface $logger,
        private AppSettingsService $appSettingsService,
        private EmailVerificationEventFactory $eventFactory,
        private EmailEventPublisherInterface $emailPublisher,
        private ClockInterface $clock
    ) {}

    public function handle(QuickSignupCommand $command, RequestContext $context): QuickSignupResponse
    {
        $this->logger->info('QuickSignup started', ['command' => $command]);

        // Бизнес-валидация
        $this->validator->validate($command);

        // Rate limit
        $this->registrationAttemptValidator->validateSignupAttempt(
            $command->email,
            $context
        );

        // Получаем значения TTL и MAX_ATTEMPTS из сервиса app-settings
        $this->ttl = $this->appSettingsService->getCodeExpirySeconds();
        $this->maxAttempts = $this->appSettingsService->getMaxVerificationAttempts();

        // Генерация requestId
        $requestId = Uuid::uuid4()->toString();

        //  вся логика в сервисе
        $code = $this->signupService->createSignupWithCode(
            $requestId,
            $command->email,
            $command->roleCode,
            $command->countryAlpha2,
            $command->acceptTerms,
            $command->acceptMarketing,
            $command->acceptMarketingVer,
            $command->urlPageCheckout,
            $command->userAgent,
            $command->ipAddress,
            $this->maxAttempts
        );

        $this->ttl = min($this->ttl, $this->signupService->remainingLifetime($requestId));
        if ($this->ttl < 1) {
            throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException('Время регистрации истекло');
        }
        $event = $this->eventFactory->create(
            $requestId,
            $command->email,
            $code,
            $this->ttl,
            $this->appSettingsService->getMaxCodeRegenerations()
        );
        $this->redis->saveOtp($requestId, $code, $this->ttl, $this->maxAttempts);
        $this->emailPublisher->publish($event);

        // Фиксация времени отправки
        $this->signupService->updateSendTime($requestId);

        $this->logger->info('QuickSignup success', ['requestId' => $requestId]);

        return new QuickSignupResponse($requestId, $this->ttl, $this->appSettingsService->getMaxCodeRegenerations(), $this->clock );
    }
}

<?php

namespace App\Application\Registration\VerificationPHONE;

use App\Application\Registration\VerificationPHONE\DTO\SendSmsResponse;
use App\Application\Registration\QuickSignup\DTO\RequestContext;
use App\Application\Registration\VerificationPHONE\DTO\VerificationCodeResponse;
use \App\Application\Registration\VerificationPHONE\SmsCodeSendAttemptValidator;
use App\Application\Registration\VerificationPHONE\Command\VerificationCodeCommand;
use App\Domain\Event\SmsVerificationEventFactory;
use App\Domain\Settings\AppSettingsService;
use App\Infrastructure\EventPublisher\HttpSmsEventPublisher;
use App\Infrastructure\Registration\Otp\Redis\RedisOtpStorage;
use App\Shared\Time\ClockInterface;
use Psr\Log\LoggerInterface;


/**
 * Класс по обработке данных второго шага регистрации
 * Отправляем проверочное SMS на номер указанного телефона
 */
final class SendPhoneVerificationCodeHandler
{
    private int $ttl;
    private int $maxAttempts;

    public function __construct(
        private VerificationCodeValidator   $validator,
        private VerificationPhoneService    $verificationUserService,
        private RedisOtpStorage             $redis,
        private SmsCodeSendAttemptValidator $registrationAttemptValidator,
        private LoggerInterface             $logger,
        private AppSettingsService          $appSettingsService,
        private SmsVerificationEventFactory $eventFactory,
        private HttpSmsEventPublisher       $smsPublisher,
        private ClockInterface              $clock
    ) {}

    public function handle(
        VerificationCodeCommand $command,
        RequestContext $context
    ): SendSmsResponse {
        $this->logger->info('SMS verification started', [
            'requestId' => $command->requestId,
            'email' => $command->phoneNumber ?? null
        ]);

        // 1. business validation
        $registration = $this->validator->validate($command);

        // 2. rate limit
        $this->registrationAttemptValidator->validateSendAttempt(
            $command->phoneNumber,
            $context
        );

        // 3. config
        $this->ttl = $this->appSettingsService->getSignupTtl();
        $this->maxAttempts = $this->appSettingsService->getMaxVerificationAttempts();

        // 4. requestId (если не пришёл извне — генерим) TODO - сделать тут ошибку!!!
      // $requestId = $command->requestId ?: Uuid::uuid4()->toString();

        // 5. core business logic (DB write)
        $code = $this->verificationUserService->createVerificationWithCode(
            $command,
            $registration,
            $this->maxAttempts
        );

        // 6. event (SMS sending)
        $event = $this->eventFactory->create(
            $command->requestId,
            $command->phoneNumber,
            $code
        );

        $this->smsPublisher->publish($event);

        // 7. cache OTP in Redis
        $this->redis->saveOtp(
            $command->requestId,
            $code,
            $this->ttl,
            $this->maxAttempts
        );

        // 8. update DB send timestamp
        $this->verificationUserService->updateSendTime($command->requestId);

        $this->logger->info('SMS verification success', [
            'requestId' => $command->requestId
        ]);

        // 9. response
        return new SendSmsResponse(
            $command->requestId,
            $this->ttl,
            $this->maxAttempts,
            $this->clock
        );
    }
}

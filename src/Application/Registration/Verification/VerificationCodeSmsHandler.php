<?php

namespace App\Application\Registration\Verification;

use App\Application\Registration\Verification\Command\VerificationCodeCommand;
use App\Application\Registration\Verification\DTO\VerificationCodeResponse;
use App\Domain\Event\CreateNewUserEventFactory;
use App\Domain\Settings\AppSettingsService;
use App\Infrastructure\EventPublisher\HttpKafkaEventPublisher;
use App\Infrastructure\Registration\VerificationUsersStorage;
use Psr\Log\LoggerInterface;


/**
 * Класс для проверкии SMS от пользователя после второго шага регистрации
 * Проверяем наличие действующей попытки ркгистрации и отправляем запрос на создание пользователя в AUTH
 */
final class VerificationCodeSmsHandler
{
    public function __construct(
        private VerificationCodeSmsValidator $validator,
        private VerificationCodeSmsService   $service,
        private LoggerInterface              $logger,
        private AppSettingsService           $appSettingsService,
        private CreateNewUserEventFactory    $eventFactory,
        private VerificationUsersStorage     $user,
        private HttpKafkaEventPublisher      $publisher
    ) {}

    public function handle(VerificationCodeCommand $command): VerificationCodeResponse
    {
        $this->logger->info('Verification sms started', [
            'requestId' => $command->requestId,
        ]);

        /*
        |--------------------------------------------------------------------------
        | 1. VALIDATION
        |--------------------------------------------------------------------------
        */

        $this->validator->validate($command);

        /*
        |--------------------------------------------------------------------------
        | 2. BUSINESS LOGIC
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

        if ($result['success'] === true) {
            $user = $this->user->findByRequestId($command->requestId);

            $event = $this->eventFactory->create(
                $user
            );

            $this->publisher->publish($event);
        }


        /*
        |--------------------------------------------------------------------------
        | 3. RESPONSE
        |--------------------------------------------------------------------------
        */

        return new VerificationCodeResponse(
            requestId: $command->requestId,
            expiresInSeconds: 600,
            verificationAttemptsLeft: $result['attemptsLeft'],
            success: $result['success'],
            message: $result['message'],
            errorCode: $result['errorCode'] ?? null
        );
    }
}

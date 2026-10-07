<?php

namespace App\Application\Registration\Verification;

use App\Application\Registration\Verification\Command\VerificationCodeCommand;
use App\Domain\Dictionaries\DictionaryDomainService;
use App\Domain\Registration\Otp\OtpStorageInterface;
use App\Domain\Settings\AppSettingsService;
use App\Infrastructure\Registration\SignupRequestStorage;
use App\Shared\Exception\BadRequestException;
use App\Shared\Exception\ErrorCode;

/**
 * Валидатор ввода проверочного кода email
 * Наличие неистекщей попытки регистрации с этим email
 */
final class VerificationCodeMailValidator
{
    public function __construct(
        private OtpStorageInterface $otpStorage,
        private SignupRequestStorage $signupStorage,
        private AppSettingsService $appSettingsService,
        private DictionaryDomainService $dictionaryDomainService
    ) {}

    public function validate(VerificationCodeCommand $command): void
    {
        /*
        |--------------------------------------------------------------------------
        | 1. Проверка существовония регистрации с этим requestId
        |--------------------------------------------------------------------------
        */

        $registration = $this->validateRegistrationExists($command->requestId);
        // Confirmed requests remain valid for downstream account creation after expiry.
        if ($registration['is_verified']) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Провекра доступности регистрации с этим каналом связи в справочнике
        |--------------------------------------------------------------------------
        */

        $this->validateChannelCode($command->channelCode);

        /*
        |--------------------------------------------------------------------------
        | 3. REGISTRATION TTL - проверка времени жизни самой регистрации
        |--------------------------------------------------------------------------
        */

        $this->validateRegistrationTtl($registration, $command->requestId);

        /*
        |--------------------------------------------------------------------------
        | 4. OTP CHECK (Redis) - проверка времени жизни проверочного кода
        |--------------------------------------------------------------------------
        */

        $otp = $this->validateOtpExists($command->requestId);

        /*
        |--------------------------------------------------------------------------
        | 5. ATTEMPTS CHECK -  проверка количества попыток ввода неправильного кода
        |--------------------------------------------------------------------------
        */

        $this->validateAttempts($otp, $command->requestId);
    }

    /*
    |--------------------------------------------------------------------------
    | REGISTRATION
    |--------------------------------------------------------------------------
    */

    private function validateRegistrationExists(string $requestId): array
    {
        $registration = $this->signupStorage->findByRequestId($requestId);

        if (!$registration) {
            throw new BadRequestException(
                'Регистрация не найдена',
                ErrorCode::B_REG_NOT_FOUND,
                ['requestId' => $requestId]
            );
        }

        return $registration;
    }

    /*
    |--------------------------------------------------------------------------
    | CHANNEL CODE (DICTIONARIES SERVICE)
    |--------------------------------------------------------------------------
    */

    private function validateChannelCode(string $channelCode): string
    {
        if (!$this->dictionaryDomainService->getVerificationChannel($channelCode)) {
            throw new BadRequestException(
                sprintf('Канал %s отсутствует в справочнике', $channelCode),
                ErrorCode::B_CHANNEL_NOT_FOUND,
                [
                    'field' => 'channelCode',
                    'value' => $channelCode
                ]
            );
        }

        return $channelCode;
    }

    /*
    |--------------------------------------------------------------------------
    | TTL CHECK
    |--------------------------------------------------------------------------
    */

    private function validateRegistrationTtl(array $registration, string $requestId): void
    {
        if (time() >= (new \DateTimeImmutable($registration['expires_at']))->getTimestamp()) {
            throw new BadRequestException(
                'Время регистрации истекло. Начните заново',
                ErrorCode::B_REG_EXPIRED,
                ['requestId' => $requestId]
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | OTP CHECK
    |--------------------------------------------------------------------------
    */

    private function validateOtpExists(string $requestId): array
    {
        $otp = $this->otpStorage->getOtp($requestId);

        if (!$otp) {
            throw new BadRequestException(
                'Код подтверждения не найден. Запросите новый код',
                ErrorCode::B_OTP_NOT_FOUND,
                ['requestId' => $requestId]
            );
        }

        return $otp;
    }

    /*
    |--------------------------------------------------------------------------
    | ATTEMPTS CHECK
    |--------------------------------------------------------------------------
    */

    private function validateAttempts(array $otp, string $requestId): void
    {
        if ($otp['attempts'] >= $otp['max_attempts']) {
            throw new BadRequestException(
                'Превышено количество попыток ввода кода',
                ErrorCode::B_OTP_ATTEMPTS_EXCEEDED,
                ['requestId' => $requestId]
            );
        }
    }
}

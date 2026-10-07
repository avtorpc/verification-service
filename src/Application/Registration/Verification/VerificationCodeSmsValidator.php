<?php

namespace App\Application\Registration\Verification;

use App\Application\Registration\Verification\Command\VerificationCodeCommand;
use App\Domain\Dictionaries\DictionaryDomainService;
use App\Domain\Registration\Otp\OtpStorageInterface;
use App\Domain\Settings\AppSettingsService;
use App\Infrastructure\Registration\SignupRequestStorage;
use App\Infrastructure\Registration\VerificationUsersStorage;
use App\Shared\Exception\BadRequestException;
use App\Shared\Exception\ErrorCode;

final class VerificationCodeSmsValidator
{
    public function __construct(
        private OtpStorageInterface $otpStorage,
        private VerificationUsersStorage $storage,
        private AppSettingsService $appSettingsService,
        private DictionaryDomainService $dictionaryDomainService
    ) {}

    public function validate(VerificationCodeCommand $command): void
    {
        /*
        |--------------------------------------------------------------------------
        | 1. REGISTRATION CHECK (DB)
        |--------------------------------------------------------------------------
        */

        $registration = $this->validateRegistrationExists($command->requestId);

        /*
        |--------------------------------------------------------------------------
        | 2. CHANNEL CHECK (DICTIONARIES SERVICE)
        |--------------------------------------------------------------------------
        */

        $this->validateChannelCode($command->channelCode);

        /*
        |--------------------------------------------------------------------------
        | 3. REGISTRATION TTL
        |--------------------------------------------------------------------------
        */

        $this->validateRegistrationTtl($registration, $command->requestId);

        /*
        |--------------------------------------------------------------------------
        | 4. OTP CHECK (Redis)
        |--------------------------------------------------------------------------
        */

        $otp = $this->validateOtpExists($command->requestId);

        /*
        |--------------------------------------------------------------------------
        | 5. ATTEMPTS CHECK
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
        $registration = $this->storage->findByRequestId($requestId);

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
        $Ttl = $this->appSettingsService->getSignupTtl();

        $createdAt = strtotime($registration['updated_at']);

        if ((time() - $createdAt) > $Ttl) {
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

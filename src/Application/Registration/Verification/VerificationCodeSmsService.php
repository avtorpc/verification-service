<?php

namespace App\Application\Registration\Verification;

use App\Domain\Registration\Otp\OtpStorageInterface;
use App\Infrastructure\Registration\SignupRequestStorage;
use App\Infrastructure\Registration\VerificationUsersStorage;

final class VerificationCodeSmsService
{
    public function __construct(
        private OtpStorageInterface $otpStorage,
        private VerificationUsersStorage $storage
    ) {}

    public function verify(
        string $requestId,
        string $code,
        int $maxAttempts
    ): array {
        /*
        |--------------------------------------------------------------------------
        | 1. OTP уже гарантирован валидатором
        |--------------------------------------------------------------------------
        */

        $attempts = 0;
        /*
        |--------------------------------------------------------------------------
        | 2. Проверка кода (единственная точка изменения attempts)
        |--------------------------------------------------------------------------
        */

        if (!$this->otpStorage->verifyCode($requestId, $code)) {

            // увеличиваем attempts только здесь
            $attempts = $this->otpStorage->incrementAttempts($requestId);

            // синхронизация с БД
            $this->storage->updateVerificationAttemptsLeft($requestId, $maxAttempts - $attempts);

            return [
                'success' => false,
                'errorCode' => 'B_SMS_VERIFICATION_CODE_MISMATCH',
                'message' => 'Код неверный',
                'attemptsLeft' => max(0, $maxAttempts - $attempts),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Успех
        |--------------------------------------------------------------------------
        */

        $this->storage->markVerified($requestId);
        $this->otpStorage->clear($requestId);

        return [
            'success' => true,
            'message' => 'Ваш телефон проверен',
            'attemptsLeft' => max(0, $maxAttempts - $attempts) ,
        ];
    }
}

<?php

namespace App\Application\Registration\Verification;

use App\Domain\Registration\Otp\OtpStorageInterface;
use App\Infrastructure\Registration\SignupRequestStorage;

final class VerificationCodeEmailService
{
    public function __construct(
        private OtpStorageInterface $otpStorage,
        private SignupRequestStorage $signupStorage
    ) {}

    public function verify(
        string $requestId,
        string $code,
        int $maxAttempts
    ): array {
        $registration = $this->signupStorage->findByRequestId($requestId);
        if ($registration && $registration['is_verified']) {
            return ['success' => true, 'message' => 'Email уже подтверждён', 'attemptsLeft' => 0];
        }

        /*
        |--------------------------------------------------------------------------
        | 1. OTP уже гарантирован валидатором
        |--------------------------------------------------------------------------
        */

        /*
        |--------------------------------------------------------------------------
        | 2. Проверка кода (единственная точка изменения attempts)
        |--------------------------------------------------------------------------
        */

        if (!$this->otpStorage->verifyCode($requestId, $code)) {

            // увеличиваем attempts только здесь
            $attempts = $this->otpStorage->incrementAttempts($requestId);

            // синхронизация с БД
            $this->signupStorage->updateVerificationAttemptsLeft($requestId, $maxAttempts - $attempts);

            return [
                'success' => false,
                'errorCode' => 'B_EMAIL_VERIFICATION_CODE_MISMATCH',
                'message' => 'Код неверный',
                'attemptsLeft' => max(0, $maxAttempts - $attempts),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Успех
        |--------------------------------------------------------------------------
        */

        $this->signupStorage->markVerified($requestId);
        $this->otpStorage->clear($requestId);

        return [
            'success' => true,
            'message' => 'Ваша почта проверена',
            'attemptsLeft' => 0,
        ];
    }
}

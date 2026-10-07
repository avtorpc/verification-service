<?php

namespace App\Application\Registration\ResendSmsCode\DTO;

use App\Shared\Time\ClockInterface;

final class ResendSmsCodeResponse
{
    public function __construct(
        private string $requestId,
        private int $expiresInSeconds,
        private int $verificationAttemptsLeft,
        private ClockInterface $clock
    ) {}

    public function toArray(): array
    {
        return [
            'success' => true,
            'timestamp' => $this->clock->nowFormatted(),
            'message' => 'Код подтверждения успешно отправлен на указанный номер телефона',
            'expiresInSeconds' => $this->expiresInSeconds,
            'data' => [
                'requestId' => $this->requestId,
                'dateTime' => $this->clock->nowFormatted(),
                'verificationAttemptsLeft' => $this->verificationAttemptsLeft,
            ],
        ];
    }
}

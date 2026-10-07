<?php

namespace App\Application\Registration\ResendCode\DTO;

use App\Shared\Time\ClockInterface;

final class ResendCodeResponse
{
    public function __construct(
        private string $requestId,
        private int $expiresInSeconds,
        private int $resendAttemptsLeft,
        private ClockInterface $clock
    ) {}

    public function toArray(): array
    {
        return [
            'success' => true,
            'timestamp' => $this->clock->nowFormatted(),
            'message' => 'Код подтверждения успешно отправлен на указанный email',
            'expiresInSeconds' => $this->expiresInSeconds,
            'data' => [
                'requestId' => $this->requestId,
                'dateTime' => $this->clock->nowFormatted(),
                'resendAttemptsLeft' => $this->resendAttemptsLeft,
            ]
        ];
    }
}

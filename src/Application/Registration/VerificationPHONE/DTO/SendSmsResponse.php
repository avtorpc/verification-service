<?php

namespace App\Application\Registration\VerificationPHONE\DTO;

use App\Shared\Time\ClockInterface;

class SendSmsResponse
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
            'message' => 'Код подтверждения успешно отправлен на указанный номер телефонв',
            'expiresInSeconds' => $this->expiresInSeconds,
            'data' => [
                'requestId' => $this->requestId,
                'dateTime' => $this->clock->nowFormatted(),
                'resendAttemptsLeft' => $this->resendAttemptsLeft,
            ]
        ];
    }
}

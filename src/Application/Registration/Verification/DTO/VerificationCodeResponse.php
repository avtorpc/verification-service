<?php

namespace App\Application\Registration\Verification\DTO;

/**
 * @psalm-suppress PossiblyUnusedProperty
 */
final class VerificationCodeResponse
{
    public function __construct(
        private string $requestId,
        private int $expiresInSeconds,
        private int $verificationAttemptsLeft,
        private bool $success,
        private string $message,
        private ?string $errorCode = null
    ) {}

    public function toArray(): array
    {
        $now = (new \DateTimeImmutable())->format(DATE_ATOM);

        $response = [
            'success' => $this->success,
            'timestamp' => $now,
            'message' => $this->message,
            'expiresInSeconds' => $this->expiresInSeconds,
            'data' => [
                'requestId' => $this->requestId,
                'dateTime' => $now,
                'verificationAttemptsLeft' => $this->verificationAttemptsLeft,
            ]
        ];

        // errorCode добавляем только при ошибке
        if ($this->errorCode !== null) {
            $response['errorCode'] = $this->errorCode;
        }

        return $response;
    }
}

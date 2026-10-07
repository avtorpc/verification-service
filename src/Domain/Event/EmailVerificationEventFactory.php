<?php

namespace App\Domain\Event;

use Ramsey\Uuid\Uuid;

class EmailVerificationEventFactory
{
    public function create(
        string $requestId,
        string $email,
        string $code,
        int $expiresInSeconds = 300,
        int $resendAttemptsLeft = 3
    ): EmailVerificationEvent {
        return new EmailVerificationEvent(
            eventId: Uuid::uuid4()->toString(),
            eventType: 'email.send.requested',
            eventVersion: 1,
            occurredAt: (new \DateTimeImmutable())->format(DATE_ATOM),

            traceId: $requestId,
            correlationId: $requestId,

            service: 'verification-service',

            requestId: $requestId,
            template: 'registration_verification',
            locale: 'en-US',
            verificationCode: $code,

            email: $email,

            expiresInSeconds: $expiresInSeconds,
            resendAttemptsLeft: $resendAttemptsLeft
        );
    }
}

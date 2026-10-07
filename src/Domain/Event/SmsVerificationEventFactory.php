<?php

namespace App\Domain\Event;

use App\Shared\Time\ClockInterface;
use Ramsey\Uuid\Uuid;

final class SmsVerificationEventFactory
{
    public function __construct(
        private readonly ClockInterface $clock
    ) {}

    public function create(
        string $requestId,
        string $phoneNumber,
        string $code
    ): SmsVerificationEvent {
        return new SmsVerificationEvent(
            eventId: Uuid::uuid4()->toString(),
            eventType: 'sms.send.requested',
            eventVersion: 1,
            occurredAt: $this->clock->nowIso(),

            traceId: $requestId,
            correlationId: $requestId,

            service: 'verification-service',

            requestId: $requestId,
            template: 'registration_verification',
            locale: 'ru-RU',
            verificationCode: $code,

            phoneNumber: $phoneNumber,

            expiresInSeconds: 60,
            resendAttemptsLeft: 0
        );
    }
}

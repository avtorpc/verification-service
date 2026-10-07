<?php

namespace App\Domain\Event;

class CreateNewUserEvent
{
    public function __construct(
        public readonly string $eventId,
        public readonly string $eventType,
        public readonly int $eventVersion,
        public readonly string $occurredAt,

        public readonly string $traceId,
        public readonly string $correlationId,

        public readonly string $service,

        public readonly string $userUuid,
        public readonly string $email,

        public readonly string $lastName,
        public readonly string $firstName,
        public readonly ?string $patronymic,

        public readonly string $phoneNumber,

        public readonly string $verificationChannelId,

        public readonly string $callback = '',
    ) {}
}

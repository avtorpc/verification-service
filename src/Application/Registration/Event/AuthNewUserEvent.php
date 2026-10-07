<?php

namespace App\Application\Registration\Event;

final readonly class AuthNewUserEvent
{
    public function __construct(
        public string $eventId,
        public string $eventType,
        public int $eventVersion,
        public string $occurredAt,
        public string $traceId,
        public string $correlationId,
        public string $service,
        public array $payload,
    ) {
    }
}

<?php

namespace App\Application\Registration\Serializer;

use App\Application\Registration\Event\AuthNewUserEvent;

class AuthNewUserSerializer
{
    public function toArray(AuthNewUserEvent $event): array
    {
        return [
            'event_id' => $event->eventId,
            'event_type' => $event->eventType,
            'event_version' => $event->eventVersion,
            'occurred_at' => $event->occurredAt,

            'trace_id' => $event->traceId,
            'correlation_id' => $event->correlationId,

            'producer' => [
                'service' => $event->service,
            ],

            'callback' => '',

            'payload' => $event->payload,
        ];
    }
}

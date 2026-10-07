<?php

namespace App\Infrastructure\EventPublisher;

use App\Domain\Event\CreateNewUserEvent;

final class CreateNewUserEventSerializer
{
    public function toArray(CreateNewUserEvent $event): array
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

            'callback' => $event->callback,

            'payload' => [
                'user_uuid' => $event->userUuid,
                'email' => $event->email,

                'last_name' => $event->lastName,
                'first_name' => $event->firstName,
                'patronymic' => $event->patronymic,

                'phone_number' => $event->phoneNumber,

                'verification_channel_id' => $event->verificationChannelId,
            ],
        ];
    }
}

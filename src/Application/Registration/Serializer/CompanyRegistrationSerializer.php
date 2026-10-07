<?php

namespace App\Application\Registration\Serializer;

use App\Application\Registration\Event\CompanyRegistrationCompletedEvent;

class CompanyRegistrationSerializer
{
    public function toArray(CompanyRegistrationCompletedEvent $event): array
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

            'payload' => [
                'company_id' => $event->companyId,
                'legal_entity' => $event->legalEntity,
                'company_name' => $event->companyName,
                'legal_form_name' => $event->legalFormName,
                'role_code' => $event->roleCode,
                'country_code' => $event->countryCode,
                'status' => $event->status,
            ],
        ];
    }
}

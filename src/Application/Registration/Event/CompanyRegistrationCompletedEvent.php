<?php

namespace App\Application\Registration\Event;

final readonly class CompanyRegistrationCompletedEvent
{
    public function __construct(
        public string $eventId,
        public string $eventType,
        public int $eventVersion,
        public string $occurredAt,
        public string $traceId,
        public string $correlationId,
        public string $service,
        public int $companyId,
        public string $legalEntity,
        public string $companyName,
        public string $legalFormName,
        public string $roleCode,
        public string $countryCode,
        public string $status,
    ) {
    }
}

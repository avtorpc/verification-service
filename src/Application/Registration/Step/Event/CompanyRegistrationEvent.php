<?php

namespace App\Application\Registration\Step\Event;

final readonly class CompanyRegistrationEvent
{
    public function __construct(
        public string $type,
        public int $companyId,
        public array $data = [],
    ) {
    }
}

<?php

namespace App\Application\CompanyContact\Mapper;

use App\Application\CompanyContact\DTO\ContactRawRequest;

class ContactJsonMapper
{
    public function map(array $data): ContactRawRequest
    {
        return new ContactRawRequest(
            companyId: isset($data['companyId']) ? (string) $data['companyId'] : null,
            contactType: $data['contactType'] ?? null,
            value: $data['value'] ?? null,
            isPrimary: isset($data['isPrimary']) ? (string) $data['isPrimary'] : null,
        );
    }
}

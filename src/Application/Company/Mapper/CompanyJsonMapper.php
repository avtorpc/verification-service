<?php

namespace App\Application\Company\Mapper;

use App\Application\Company\DTO\CompanyRawRequest;

class CompanyJsonMapper
{
    public function map(array $data): CompanyRawRequest
    {
        return new CompanyRawRequest(
            legalEntity: $data['legalEntity'] ?? null,
            legalFormName: $data['legalFormName'] ?? null,
            companyName: $data['companyName'] ?? null,
            roleCode: $data['roleCode'] ?? null,
            countryCode: $data['countryCode'] ?? null,
            isKzNdsApplicable: isset($data['isKzNdsApplicable']) ? (string) $data['isKzNdsApplicable'] : null,
            isNdsPayer: isset($data['isNdsPayer']) ? (string) $data['isNdsPayer'] : null,
            status: $data['status'] ?? null,
        );
    }
}

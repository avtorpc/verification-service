<?php

namespace App\Application\Company\Mapper;

use App\Application\Company\DTO\CompanyDomainRequest;
use App\Application\Company\DTO\CompanyRawRequest;
use App\Shared\Exception\ValidationException;

class CompanyDomainMapper
{
    private const ALLOWED_STATUSES = ['draft', 'moderation', 'approved', 'rejected'];

    public function map(CompanyRawRequest $raw): CompanyDomainRequest
    {
        if ($raw->legalEntity === null || trim($raw->legalEntity) === '') {
            throw new ValidationException('legalEntity is required');
        }

        if ($raw->countryCode === null || trim($raw->countryCode) === '') {
            throw new ValidationException('countryCode is required');
        }

        if ($raw->companyName === null || trim($raw->companyName) === '') {
            throw new ValidationException('companyName is required');
        }

        if ($raw->legalFormName === null || trim($raw->legalFormName) === '') {
            throw new ValidationException('legalFormName is required');
        }

        $status = $raw->status ?? 'draft';
        if (!in_array($status, self::ALLOWED_STATUSES, true)) {
            throw new ValidationException(
                sprintf('Invalid status "%s". Allowed: %s', $status, implode(', ', self::ALLOWED_STATUSES))
            );
        }

        return new CompanyDomainRequest(
            legalEntity: trim($raw->legalEntity),
            legalFormName: trim($raw->legalFormName),
            companyName: trim($raw->companyName),
            countryCode: trim($raw->countryCode),
            status: $status,
            roleCode: $raw->roleCode !== null ? trim($raw->roleCode) : null,
            isKzNdsApplicable: $raw->isKzNdsApplicable !== null ? filter_var($raw->isKzNdsApplicable, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : null,
            isNdsPayer: $raw->isNdsPayer !== null ? filter_var($raw->isNdsPayer, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : null,
        );
    }
}

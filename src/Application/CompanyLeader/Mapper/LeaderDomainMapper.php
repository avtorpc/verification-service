<?php

namespace App\Application\CompanyLeader\Mapper;

use App\Application\CompanyLeader\DTO\LeaderDomainRequest;
use App\Application\CompanyLeader\DTO\LeaderRawRequest;
use App\Shared\Exception\ValidationException;

class LeaderDomainMapper
{
    public function map(LeaderRawRequest $raw): LeaderDomainRequest
    {
        $errors = [];

        if ($raw->companyId === null || !ctype_digit($raw->companyId)) {
            $errors[] = 'companyId is required and must be an integer';
        }

        if ($raw->firstName === null || trim($raw->firstName) === '') {
            $errors[] = 'firstName is required';
        }

        if ($raw->lastName === null || trim($raw->lastName) === '') {
            $errors[] = 'lastName is required';
        }

        if ($raw->documentTypeCode === null || trim($raw->documentTypeCode) === '') {
            $errors[] = 'documentTypeCode is required';
        }

        if (!empty($errors)) {
            throw new ValidationException(implode('; ', $errors));
        }

        return new LeaderDomainRequest(
            companyId: (int) $raw->companyId,
            firstName: trim($raw->firstName),
            lastName: trim($raw->lastName),
            documentTypeCode: trim($raw->documentTypeCode),
            patronymic: $raw->patronymic !== null ? trim($raw->patronymic) : null,
        );
    }
}

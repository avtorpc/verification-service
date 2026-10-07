<?php

namespace App\Application\CompanyInfo\Mapper;

use App\Application\CompanyInfo\DTO\GetCompanyInfoDto;
use App\Application\CompanyInfo\DTO\GetCompanyInfoRawDto;
use App\Shared\Exception\ValidationException;

final class GetCompanyInfoDomainMapper
{
    public static function map(GetCompanyInfoRawDto $raw): GetCompanyInfoDto
    {
        return new GetCompanyInfoDto(
            userUuid: self::uuid($raw->uuid),
            companyId: self::companyId($raw->company)
        );
    }

    private static function uuid(mixed $value): string
    {
        if ($value === null || $value === '') {
            throw new ValidationException('uuid is required');
        }

        if (!is_string($value)) {
            throw new ValidationException('uuid must be string');
        }

        $value = trim($value);

        if (!preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[1-5][0-9a-fA-F]{3}-[89abAB][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $value)) {
            throw new ValidationException('uuid is invalid');
        }

        return strtolower($value);
    }

    private static function companyId(mixed $value): int
    {
        if ($value === null || $value === '') {
            throw new ValidationException('company is required');
        }

        if (is_string($value) && ctype_digit($value)) {
            $value = (int) $value;
        }

        if (!is_int($value) || $value <= 0) {
            throw new ValidationException('company is invalid');
        }

        return $value;
    }
}

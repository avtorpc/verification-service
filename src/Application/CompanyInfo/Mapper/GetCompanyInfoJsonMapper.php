<?php

namespace App\Application\CompanyInfo\Mapper;

use App\Application\CompanyInfo\DTO\GetCompanyInfoRawDto;
use App\Shared\Exception\ValidationException;

final class GetCompanyInfoJsonMapper
{
    public static function fromJson(string $json): GetCompanyInfoRawDto
    {
        $data = json_decode($json, true);

        if (!is_array($data)) {
            throw new ValidationException('Invalid JSON');
        }

        $dto = new GetCompanyInfoRawDto();
        $dto->uuid = $data['uuid'] ?? null;
        $dto->company = $data['company'] ?? null;

        return $dto;
    }
}

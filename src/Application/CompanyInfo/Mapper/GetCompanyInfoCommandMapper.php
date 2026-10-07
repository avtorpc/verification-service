<?php

namespace App\Application\CompanyInfo\Mapper;

use App\Application\CompanyInfo\Command\GetCompanyInfoCommand;
use App\Application\CompanyInfo\DTO\GetCompanyInfoDto;

final class GetCompanyInfoCommandMapper
{
    public static function mapRequestToCommand(
        GetCompanyInfoDto $request,
        string $tokenUserUuid
    ): GetCompanyInfoCommand {
        return new GetCompanyInfoCommand(
            requestUserUuid: $request->userUuid,
            tokenUserUuid: strtolower($tokenUserUuid),
            companyId: $request->companyId
        );
    }
}

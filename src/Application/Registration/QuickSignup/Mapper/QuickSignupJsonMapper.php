<?php

namespace App\Application\Registration\QuickSignup\Mapper;

use App\Application\Registration\QuickSignup\DTO\QuickSignupRawRequest;
use App\Shared\Exception\BadRequestException;
use App\Shared\Exception\ErrorCode;

final class QuickSignupJsonMapper
{
    public static function fromJson(string $json): QuickSignupRawRequest
    {
        $data = json_decode($json, true);

        if (!is_array($data)) {
            throw new BadRequestException(
                'Invalid JSON',
                ErrorCode::S_BAD_REQUEST
            );
        }

        $dto = new QuickSignupRawRequest();

        $dto->roleCode = $data['roleCode'] ?? null;
        $dto->countryAlpha2 = $data['countryAlpha2'] ?? null;
        $dto->email = $data['email'] ?? null;

        $dto->acceptMarketingVer = $data['acceptMarketingVer'] ?? null;
        $dto->urlPageCheckout = $data['urlPageCheckout'] ?? null;

        $dto->userAgent = $data['userAgent'] ?? null;

        $dto->acceptTerms = $data['acceptTerms'] ?? null;
        $dto->acceptMarketing = $data['acceptMarketing'] ?? null;

        return $dto;
    }
}

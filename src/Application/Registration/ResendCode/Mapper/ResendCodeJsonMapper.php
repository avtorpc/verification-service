<?php

namespace App\Application\Registration\ResendCode\Mapper;

use App\Application\Registration\ResendCode\DTO\ResendCodeRawDto;
use App\Shared\Exception\BadRequestException;
use App\Shared\Exception\ErrorCode;

final class ResendCodeJsonMapper
{
    public static function fromJson(string $json): ResendCodeRawDto
    {
        $data = json_decode($json, true);

        if (!is_array($data)) {
            throw new BadRequestException(
                'Invalid JSON',
                ErrorCode::S_BAD_REQUEST
            );
        }

        $dto = new ResendCodeRawDto();

        $dto->requestId = $data['requestId'] ?? null;
        $dto->urlPageCheckout = $data['urlPageCheckout'] ?? null;
        $dto->userAgent = $data['userAgent'] ?? null;

        return $dto;
    }
}

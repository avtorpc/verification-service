<?php

namespace App\Application\Registration\ResendSmsCode\Mapper;

use App\Application\Registration\ResendSmsCode\DTO\ResendSmsCodeRawDto;
use App\Shared\Exception\BadRequestException;
use App\Shared\Exception\ErrorCode;

final class ResendSmsCodeJsonMapper
{
    public static function fromJson(string $json): ResendSmsCodeRawDto
    {
        $data = json_decode($json, true);

        if (!is_array($data)) {
            throw new BadRequestException(
                'Invalid JSON',
                ErrorCode::S_BAD_REQUEST
            );
        }

        $dto = new ResendSmsCodeRawDto();
        $dto->requestId = $data['requestId'] ?? null;
        $dto->userAgent = $data['userAgent'] ?? null;

        return $dto;
    }
}

<?php

namespace App\Application\Registration\Verification\Mapper;

use App\Application\Registration\Verification\DTO\VerificationCodeRawDto;
use App\Shared\Exception\BadRequestException;
use App\Shared\Exception\ErrorCode;

final class VerificationCodeJsonMapper
{
    public static function fromJson(string $json): VerificationCodeRawDto
    {
        $data = json_decode($json, true);

        if (!is_array($data)) {
            throw new BadRequestException(
                'Invalid JSON',
                ErrorCode::S_BAD_REQUEST
            );
        }

        $dto = new VerificationCodeRawDto();

        $dto->requestId = $data['requestId'] ?? null;
        $dto->channelCode = $data['channelCode'] ?? null;
        $dto->verificationCode = $data['verificationCode'] ?? null;

        return $dto;
    }
}

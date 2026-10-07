<?php

namespace App\Application\Registration\VerificationPHONE\Mapper;

use App\Application\Registration\VerificationPHONE\DTO\VerificationCodeRawDto;
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

        /*
        |--------------------------------------------------------------------------
        | Request data
        |--------------------------------------------------------------------------
        */

        $dto->requestId = $data['requestId'] ?? null;

        /*
        |--------------------------------------------------------------------------
        | Company
        |--------------------------------------------------------------------------
        */

        $dto->legalEntity = $data['legalEntity'] ?? null;
        $dto->countryAlpha2 = $data['countryAlpha2'] ?? null;

        /*
        |--------------------------------------------------------------------------
        | User
        |--------------------------------------------------------------------------
        */

        $dto->lastName = $data['lastName'] ?? null;
        $dto->firstName = $data['firstName'] ?? null;
        $dto->patronymic = $data['patronymic'] ?? null;
        $dto->email = $data['email'] ?? null;
        $dto->phoneNumber = $data['phoneNumber'] ?? null;

        /*
        |--------------------------------------------------------------------------
        | Delivery channel
        |--------------------------------------------------------------------------
        */

        $dto->channelCode = $data['channelCode'] ?? null;

        return $dto;
    }
}

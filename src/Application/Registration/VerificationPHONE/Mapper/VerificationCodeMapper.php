<?php

namespace App\Application\Registration\VerificationPHONE\Mapper;

use App\Application\Registration\VerificationPHONE\Command\VerificationCodeCommand;
use App\Application\Registration\VerificationPHONE\DTO\VerificationCodeRequest;

final class VerificationCodeMapper
{
    public static function mapRequestToCommand(
        VerificationCodeRequest $request
    ): VerificationCodeCommand {
        return new VerificationCodeCommand(
            requestId: $request->requestId,
            legalEntity: $request->legalEntity,
            countryAlpha2: $request->countryAlpha2,
            lastName: $request->lastName,
            email: $request->email,
            firstName: $request->firstName,
            patronymic: $request->patronymic,
            phoneNumber: $request->phoneNumber,
            channelCode: $request->channelCode,
        );
    }
}

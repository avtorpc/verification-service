<?php

namespace App\Application\Registration\Verification\Mapper;

use App\Application\Registration\Verification\DTO\VerificationCodeRequest;
use App\Application\Registration\Verification\Command\VerificationCodeCommand;

final class VerificationCodeMapper
{
    public static function mapRequestToCommand(
        VerificationCodeRequest $request
    ): VerificationCodeCommand {
        return new VerificationCodeCommand(
            requestId: $request->requestId,
            channelCode: $request->channelCode,
            verificationCode: $request->verificationCode
        );
    }
}

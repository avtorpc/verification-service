<?php

namespace App\Application\Registration\ResendSmsCode\Mapper;

use App\Application\Registration\ResendSmsCode\Command\ResendSmsCodeCommand;
use App\Application\Registration\ResendSmsCode\DTO\ResendSmsCodeRequest;

final class ResendSmsCodeCommandMapper
{
    public static function mapRequestToCommand(
        ResendSmsCodeRequest $request,
        ?string $ip
    ): ResendSmsCodeCommand {
        return new ResendSmsCodeCommand(
            requestId: $request->requestId,
            userAgent: $request->userAgent,
            ip: $ip
        );
    }
}

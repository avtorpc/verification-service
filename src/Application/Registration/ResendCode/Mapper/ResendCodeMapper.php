<?php

namespace App\Application\Registration\ResendCode\Mapper;

use App\Application\Registration\ResendCode\DTO\ResendCodeRequest;
use App\Application\Registration\ResendCode\Command\ResendCodeCommand;

final class ResendCodeMapper
{
    public static function mapRequestToCommand(
        ResendCodeRequest $dto,
        ?string $ip
    ): ResendCodeCommand {
        return new ResendCodeCommand(
            requestId: $dto->requestId,
            urlPageCheckout: $dto->urlPageCheckout,
            userAgent: $dto->userAgent,
            ip: $ip
        );
    }
}

<?php

namespace App\Application\Registration\ChannelHandlers;

use App\Application\Registration\Verification\ChannelHandlers\VerificationCodeCommand;
use App\Application\Registration\Verification\ChannelHandlers\VerificationCodeResponse;
use App\Application\Registration\Verification\ChannelHandlers\VerificationCodeService;

final class EmailVerificationHandler implements VerificationChannelHandlerInterface
{
    public function __construct(
        private VerificationCodeService $service
    ) {}

    public function channel(): string
    {
        return 'email';
    }

    public function handle(
        VerificationCodeCommand $command
    ): VerificationCodeResponse {

        $result = $this->service->verifyEmail(
            $command->requestId,
            $command->verificationCode
        );

        return new VerificationCodeResponse(
            requestId: $command->requestId,
            success: $result['success'],
            message: $result['message']
        );
    }
}

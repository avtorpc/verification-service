<?php

namespace App\Application\Registration\ChannelHandlers;

use App\Application\Registration\Verification\ChannelHandlers\VerificationCodeCommand;
use App\Application\Registration\Verification\ChannelHandlers\VerificationCodeResponse;
use App\Application\Registration\Verification\ChannelHandlers\VerificationCodeService;

final class SmsVerificationHandler implements VerificationChannelHandlerInterface
{
    public function __construct(
        private VerificationCodeService $service
    ) {}

    public function channel(): string
    {
        return 'sms';
    }

    public function handle(
        VerificationCodeCommand $command
    ): VerificationCodeResponse {

        $result = $this->service->verifySms(
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

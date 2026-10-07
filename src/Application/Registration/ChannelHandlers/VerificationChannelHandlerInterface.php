<?php

namespace App\Application\Registration\ChannelHandlers;

use App\Application\Registration\Verification\ChannelHandlers\VerificationCodeCommand;
use App\Application\Registration\Verification\ChannelHandlers\VerificationCodeResponse;

interface VerificationChannelHandlerInterface
{
    public function channel(): string;

    public function handle(
        VerificationCodeCommand $command
    ): VerificationCodeResponse;
}

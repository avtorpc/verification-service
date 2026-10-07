<?php

namespace App\Shared\Utils;

class VerificationCodeGenerator
{
    public function generate(): string
    {
        return (string) random_int(100000, 999999);
    }
}

<?php

namespace App\Application\Registration\ResendSmsCode\Mapper;

use App\Application\Registration\ResendSmsCode\DTO\ResendSmsCodeRawDto;
use App\Application\Registration\ResendSmsCode\DTO\ResendSmsCodeRequest;
use App\Shared\Exception\BadRequestException;
use App\Shared\Exception\ErrorCode;

final class ResendSmsCodeDomainMapper
{
    public static function map(ResendSmsCodeRawDto $raw): ResendSmsCodeRequest
    {
        return new ResendSmsCodeRequest(
            requestId: self::uuid($raw->requestId),
            userAgent: self::nullableString($raw->userAgent),
        );
    }

    private static function uuid(mixed $value): string
    {
        if ($value === null || $value === '') {
            throw new BadRequestException(
                'requestId is required',
                ErrorCode::B_OTP_REQUEST_ID_REQUIRED,
                ['field' => 'requestId']
            );
        }

        if (!is_string($value)) {
            throw new BadRequestException(
                'requestId must be string',
                ErrorCode::B_FIELD_MUST_BE_STRING,
                ['field' => 'requestId']
            );
        }

        $value = trim($value);

        if (!preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[1-5][0-9a-fA-F]{3}-[89abAB][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $value)) {
            throw new BadRequestException(
                'requestId is invalid',
                ErrorCode::B_OTP_REQUEST_ID_INVALID,
                ['field' => 'requestId']
            );
        }

        return strtolower($value);
    }

    private static function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!is_string($value)) {
            throw new BadRequestException(
                'userAgent must be string',
                ErrorCode::B_FIELD_MUST_BE_STRING,
                ['field' => 'userAgent']
            );
        }

        return trim($value);
    }
}

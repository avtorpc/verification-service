<?php

namespace App\Application\Registration\ResendCode\Mapper;

use App\Application\Registration\ResendCode\DTO\ResendCodeRawDto;
use App\Application\Registration\ResendCode\DTO\ResendCodeRequest;
use App\Shared\Exception\BadRequestException;
use App\Shared\Exception\ErrorCode;

final class ResendCodeDomainMapper
{
    public static function map(ResendCodeRawDto $raw): ResendCodeRequest
    {
        return new ResendCodeRequest(
            requestId: self::uuid($raw->requestId),
            urlPageCheckout: self::stringOrNull($raw->urlPageCheckout),
            userAgent: self::stringOrNull($raw->userAgent),
        );
    }

    private static function uuid(mixed $value): string
    {
        if (!is_string($value) || $value === '') {
            throw new BadRequestException(
                'requestId is required',
                ErrorCode::B_OTP_REQUEST_ID_REQUIRED,
                ['field' => 'requestId']
            );
        }

        if (!preg_match('/^[0-9a-fA-F-]{36}$/', $value)) {
            throw new BadRequestException(
                'requestId is invalid',
                ErrorCode::B_OTP_REQUEST_ID_INVALID,
                ['field' => 'requestId']
            );
        }

        return strtolower($value);
    }

    private static function stringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!is_string($value)) {
            throw new BadRequestException(
                'Field must be string',
                ErrorCode::B_FIELD_MUST_BE_STRING,
                ['value' => $value]
            );
        }

        return trim($value);
    }
}

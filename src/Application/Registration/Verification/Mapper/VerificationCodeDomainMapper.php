<?php

declare(strict_types=1);

namespace App\Application\Registration\Verification\Mapper;

use App\Application\Registration\Verification\DTO\VerificationCodeRawDto;
use App\Application\Registration\Verification\DTO\VerificationCodeRequest;
use App\Shared\Exception\BadRequestException;
use App\Shared\Exception\ErrorCode;

final class VerificationCodeDomainMapper
{
    private const FIELD_LABELS = [
        'requestId' => 'ID запроса',
        'channelCode' => 'Канал связи',
        'verificationCode' => 'Код подтверждения',
    ];

    public static function map(VerificationCodeRawDto $raw): VerificationCodeRequest
    {
        return new VerificationCodeRequest(
            requestId: self::uuid($raw->requestId),
            channelCode: self::channelCode($raw->channelCode),
            verificationCode: self::verificationCode($raw->verificationCode),
        );
    }

    /*
    |--------------------------------------------------------------------------
    | ERROR HELPER
    |--------------------------------------------------------------------------
    */

    private static function fail(
        string $field,
        ErrorCode $code,
        string $reason,
        mixed $value = null,
        array $extra = []
    ): never {
        throw new BadRequestException(
            message: self::FIELD_LABELS[$field] . ': некорректное значение',
            errorCode: $code,
            context: array_merge([
                'field' => $field,
                'reason' => $reason,
                'value' => $value,
                'location' => self::class,
            ], $extra)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | UUID
    |--------------------------------------------------------------------------
    */

    private static function uuid(mixed $value): string
    {
        if ($value === null || $value === '') {
            self::fail(
                field: 'requestId',
                code: ErrorCode::B_FIELD_REQUIRED,
                reason: 'missing_required_field'
            );
        }

        if (!is_string($value)) {
            self::fail(
                field: 'requestId',
                code: ErrorCode::B_FIELD_MUST_BE_STRING,
                reason: 'invalid_type',
                value: gettype($value)
            );
        }

        $value = trim($value);

        if (!preg_match(
            '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[1-5][0-9a-fA-F]{3}-[89abAB][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/',
            $value
        )) {
            self::fail(
                field: 'requestId',
                code: ErrorCode::B_OTP_REQUEST_ID_INVALID,
                reason: 'invalid_uuid_format',
                value: $value
            );
        }

        return strtolower($value);
    }

    /*
    |--------------------------------------------------------------------------
    | CHANNEL CODE
    |--------------------------------------------------------------------------
    */

    private static function channelCode(mixed $value): string
    {
        if ($value === null || $value === '') {
            self::fail(
                field: 'channelCode',
                code: ErrorCode::B_FIELD_REQUIRED,
                reason: 'missing_required_field'
            );
        }

        if (!is_string($value)) {
            self::fail(
                field: 'channelCode',
                code: ErrorCode::B_FIELD_MUST_BE_STRING,
                reason: 'invalid_type',
                value: gettype($value)
            );
        }

        $value = strtolower(trim($value));

        // базовая защита формата (не dictionaries-check!)
        if (!preg_match('/^[a-z0-9_]{2,30}$/', $value)) {
            self::fail(
                field: 'channelCode',
                code: ErrorCode::B_CHANNEL_INVALID_FORMAT,
                reason: 'invalid_format',
                value: $value,
                extra: [
                    'expected' => 'lowercase alphanumeric with underscore'
                ]
            );
        }

        return $value;
    }

    /*
    |--------------------------------------------------------------------------
    | VERIFICATION CODE
    |--------------------------------------------------------------------------
    */

    private static function verificationCode(mixed $value): string
    {
        if ($value === null || $value === '') {
            self::fail(
                field: 'verificationCode',
                code: ErrorCode::B_FIELD_REQUIRED,
                reason: 'missing_required_field'
            );
        }

        if (is_int($value)) {
            $value = (string) $value;
        }

        if (!is_string($value)) {
            self::fail(
                field: 'verificationCode',
                code: ErrorCode::B_FIELD_MUST_BE_STRING,
                reason: 'invalid_type',
                value: gettype($value)
            );
        }

        $value = trim($value);

        if (!preg_match('/^\d{5}$/', $value)) {
            self::fail(
                field: 'verificationCode',
                code: ErrorCode::B_OTP_CODE_INVALID,
                reason: 'invalid_format',
                value: $value,
                extra: [
                    'expected' => '5_digits_numeric'
                ]
            );
        }

        return $value;
    }
}

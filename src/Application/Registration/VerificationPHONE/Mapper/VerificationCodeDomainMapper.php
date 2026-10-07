<?php

declare(strict_types=1);

namespace App\Application\Registration\VerificationPHONE\Mapper;

use App\Application\Registration\VerificationPHONE\DTO\VerificationCodeRawDto;
use App\Application\Registration\VerificationPHONE\DTO\VerificationCodeRequest;
use App\Shared\Exception\BadRequestException;
use App\Shared\Exception\ErrorCode;

final class VerificationCodeDomainMapper
{
    private const FIELD_LABELS = [
        'requestId'      => 'ID запроса',
        'legalEntity'    => 'Тип юридического лица',
        'countryAlpha2'  => 'Страна',
        'lastName'       => 'Фамилия',
        'firstName'      => 'Имя',
        'patronymic'     => 'Отчество',
        'email'          => 'Email',
        'phoneNumber'    => 'Телефон',
        'channelCode'    => 'Канал связи',
    ];

    public static function map(VerificationCodeRawDto $raw): VerificationCodeRequest
    {
        return new VerificationCodeRequest(
            requestId: self::uuid($raw->requestId),
            legalEntity: self::code($raw->legalEntity, 'legalEntity'),
            countryAlpha2: self::countryAlpha2($raw->countryAlpha2),
            lastName: self::requiredString($raw->lastName, 'lastName'),
            email: self::email($raw->email),
            firstName: self::requiredString($raw->firstName, 'firstName'),
            patronymic: self::nullableString($raw->patronymic),
            phoneNumber: self::phoneNumber($raw->phoneNumber),
            channelCode: self::channelCode($raw->channelCode),
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
    | COMMON
    |--------------------------------------------------------------------------
    */

    private static function requiredString(mixed $value, string $field): string
    {
        if ($value === null || $value === '') {
            self::fail(
                field: $field,
                code: ErrorCode::B_FIELD_REQUIRED,
                reason: 'missing_required_field'
            );
        }

        if (!is_string($value)) {
            self::fail(
                field: $field,
                code: ErrorCode::B_FIELD_MUST_BE_STRING,
                reason: 'invalid_type',
                value: gettype($value)
            );
        }

        return trim($value);
    }

    private static function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    /*
    |--------------------------------------------------------------------------
    | REQUEST ID
    |--------------------------------------------------------------------------
    */

    private static function uuid(mixed $value): string
    {
        $value = self::requiredString($value, 'requestId');

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
    | COUNTRY
    |--------------------------------------------------------------------------
    */

    private static function countryAlpha2(mixed $value): string
    {
        $value = strtoupper(self::requiredString($value, 'countryAlpha2'));

        if (!preg_match('/^[A-Z]{2}$/', $value)) {
            self::fail(
                field: 'countryAlpha2',
                code: ErrorCode::B_COUNTRY_INVALID_FORMAT,
                reason: 'invalid_format',
                value: $value
            );
        }

        return $value;
    }

    /*
    |--------------------------------------------------------------------------
    | EMAIL
    |--------------------------------------------------------------------------
    */

    private static function email(mixed $value): string
    {
        $value = mb_strtolower(self::requiredString($value, 'email'));

        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            self::fail(
                field: 'email',
                code: ErrorCode::B_EMAIL_INVALID,
                reason: 'invalid_email',
                value: $value
            );
        }

        return $value;
    }

    /*
    |--------------------------------------------------------------------------
    | PHONE
    |--------------------------------------------------------------------------
    */

    private static function phoneNumber(mixed $value): string
    {
        $value = self::requiredString($value, 'phoneNumber');

        if (!preg_match('/^\+?[0-9]{6,20}$/', $value)) {
            self::fail(
                field: 'phoneNumber',
                code: ErrorCode::B_PHONE_INVALID,
                reason: 'invalid_format',
                value: $value
            );
        }

        return $value;
    }

    /*
    |--------------------------------------------------------------------------
    | CODES
    |--------------------------------------------------------------------------
    */

    private static function channelCode(mixed $value): string
    {
        return self::code($value, 'channelCode');
    }

    private static function code(mixed $value, string $field): string
    {
        $value = strtolower(self::requiredString($value, $field));

        if (!preg_match('/^[a-z0-9_]{2,30}$/', $value)) {
            self::fail(
                field: $field,
                code: ErrorCode::B_CHANNEL_INVALID_FORMAT,
                reason: 'invalid_format',
                value: $value
            );
        }

        return $value;
    }
}

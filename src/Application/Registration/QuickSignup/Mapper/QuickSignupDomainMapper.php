<?php

namespace App\Application\Registration\QuickSignup\Mapper;

use App\Application\Registration\QuickSignup\DTO\QuickSignupRawRequest;
use App\Application\Registration\QuickSignup\DTO\QuickSignupRequest;
use App\Shared\Exception\BadRequestException;
use App\Shared\Exception\ErrorCode;

/**
 * DomainMapper = данные валидны по форме
 */
final class QuickSignupDomainMapper
{
    private const FIELD_LABELS = [
        'roleCode' => 'Роль пользователя',
        'countryAlpha2' => 'Код страны',
        'email' => 'Email',
        'acceptMarketingVer' => 'Версия согласия на маркетинг',
        'urlPageCheckout' => 'URL страницы оформления',
        'userAgent' => 'User-Agent',
        'acceptTerms' => 'Согласие с условиями',
        'acceptMarketing' => 'Согласие на маркетинг',
    ];

    public static function map(QuickSignupRawRequest $raw): QuickSignupRequest
    {
        return new QuickSignupRequest(
            roleCode: self::string($raw->roleCode, 'roleCode'),
            countryAlpha2: self::country($raw->countryAlpha2, 'countryAlpha2'),
            email: self::email($raw->email, 'email'),

            acceptMarketingVer: self::string($raw->acceptMarketingVer, 'acceptMarketingVer'),
            urlPageCheckout: self::string($raw->urlPageCheckout, 'urlPageCheckout'),

            userAgent: self::nullableString($raw->userAgent, 'userAgent'),

            acceptTerms: self::bool($raw->acceptTerms, 'acceptTerms'),
            acceptMarketing: self::bool($raw->acceptMarketing, 'acceptMarketing'),
        );
    }

    private static function fail(string $field, ErrorCode $code): never
    {
        $label = self::FIELD_LABELS[$field] ?? $field;

        throw new BadRequestException(
            "{$label}: некорректное значение",
            $code
        );
    }

    // =========================
    // STRING
    // =========================
    private static function string(mixed $value, string $field): string
    {
        if ($value === null || $value === '') {
            self::fail($field, ErrorCode::B_FIELD_REQUIRED);
        }

        if (!is_string($value)) {
            self::fail($field, ErrorCode::B_FIELD_MUST_BE_STRING);
        }

        return trim($value);
    }

    private static function nullableString(mixed $value, string $field): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!is_string($value)) {
            self::fail($field, ErrorCode::B_FIELD_MUST_BE_STRING);
        }

        return $value; // допускаем ""
    }

    // =========================
    // BOOL
    // =========================
    private static function bool(mixed $value, string $field): bool
    {
        if ($value === null) {
            self::fail($field, ErrorCode::B_FIELD_REQUIRED);
        }

        if (!is_bool($value)) {
            self::fail($field, ErrorCode::B_FIELD_MUST_BE_BOOL);
        }

        return $value;
    }

    // =========================
    // EMAIL
    // =========================
    private static function email(mixed $value, string $field): string
    {
        if ($value === null || $value === '') {
            self::fail($field, ErrorCode::B_FIELD_REQUIRED);
        }

        if (!is_string($value)) {
            self::fail($field, ErrorCode::B_FIELD_MUST_BE_STRING);
        }

        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            self::fail($field, ErrorCode::B_EMAIL_INVALID);
        }

        return $value;
    }

    // =========================
    // COUNTRY
    // =========================
    private static function country(mixed $value, string $field): string
    {
        if ($value === null || $value === '') {
            self::fail($field, ErrorCode::B_FIELD_REQUIRED);
        }

        if (!is_string($value)) {
            self::fail($field, ErrorCode::B_FIELD_MUST_BE_STRING);
        }

        if (strlen($value) !== 2) {
            self::fail($field, ErrorCode::B_VALIDATION_FAILED);
        }

        return strtoupper($value);
    }
}

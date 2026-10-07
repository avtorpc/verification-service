<?php

declare(strict_types=1);

namespace App\Application\Registration\VerificationPHONE;

use App\Application\Registration\VerificationPHONE\Command\VerificationCodeCommand;
use App\Domain\Dictionaries\DictionaryDomainService;
use App\Infrastructure\Registration\SignupRequestStorage;
use App\Shared\Exception\BadRequestException;
use App\Shared\Exception\ErrorCode;

final class VerificationCodeValidator
{
    public function __construct(
        private SignupRequestStorage $signupStorage,
        private DictionaryDomainService $dictionaryDomainService
    ) {
    }

    public function validate(VerificationCodeCommand $command): array
    {
        /*
        |--------------------------------------------------------------------------
        | 1. REGISTRATION CHECK
        |--------------------------------------------------------------------------
        */

        $registration = $this->validateRegistrationExists($command->requestId);

        /*
        |--------------------------------------------------------------------------
        | 2. EMAIL CHECK
        |--------------------------------------------------------------------------
        */

        $this->validateEmail(
            $command->email,
            $registration
        );

        /*
        |--------------------------------------------------------------------------
        | 3. CHANNEL CHECK
        |--------------------------------------------------------------------------
        */

        $this->validateChannelCode($command->channelCode);

        /*
        |--------------------------------------------------------------------------
        | 4. ROLE + COUNTRY CHECK
        |--------------------------------------------------------------------------
        */

        $this->validateRoleCountry($registration);

        return $registration;
    }

    private function validateRegistrationExists(string $requestId): array
    {
        $registration = $this->signupStorage->findByRequestId($requestId);

        if ($registration === null) {
            throw new BadRequestException(
                'Регистрация не найдена',
                ErrorCode::B_REG_NOT_FOUND,
                [
                    'requestId' => $requestId,
                ]
            );
        }

        return $registration;
    }

    private function validateEmail(
        string $email,
        array $registration
    ): void {
        $registeredEmail = $registration['email'] ?? null;

        if ($registeredEmail === null) {
            throw new BadRequestException(
                'В регистрационных данных отсутствует email',
                ErrorCode::B_REG_NOT_FOUND
            );
        }

        if (
            mb_strtolower(trim($email))
            !==
            mb_strtolower(trim($registeredEmail))
        ) {
            throw new BadRequestException(
                'Email не соответствует данным первого этапа регистрации',
                ErrorCode::B_EMAIL_MISMATCH,
                [
                    'field' => 'email',
                    'value' => $email,
                ]
            );
        }
    }

    private function validateChannelCode(string $channelCode): void
    {
        if (!$this->dictionaryDomainService->getVerificationChannel($channelCode)) {
            throw new BadRequestException(
                sprintf(
                    'Канал "%s" отсутствует в справочнике',
                    $channelCode
                ),
                ErrorCode::B_CHANNEL_NOT_FOUND,
                [
                    'field' => 'channelCode',
                    'value' => $channelCode,
                ]
            );
        }
    }

    private function validateRoleCountry(array $registration): void
    {
        $roleCode = $registration['role_code'] ?? null;
        $countryAlpha2 = $registration['country_alpha2'] ?? null;

        if ($roleCode === null || $countryAlpha2 === null) {
            throw new BadRequestException(
                'В регистрационных данных отсутствуют roleCode или countryAlpha2',
                ErrorCode::B_REG_NOT_FOUND
            );
        }

        $this->dictionaryDomainService->validateRoleCountry(
            $roleCode,
            $countryAlpha2
        );
    }
}

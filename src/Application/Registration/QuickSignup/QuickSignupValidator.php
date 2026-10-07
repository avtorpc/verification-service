<?php

namespace App\Application\Registration\QuickSignup;

use App\Application\Registration\QuickSignup\Command\QuickSignupCommand;
use App\Domain\Dictionaries\DictionaryDomainService;
use App\Shared\Exception\ErrorCode;
use App\Shared\Exception\UnprocessableEntityException;

final class QuickSignupValidator
{
    public function __construct(
        private DictionaryDomainService $dictionaryDomainService
    ) {}

    public function validate(QuickSignupCommand $command): void
    {
        // 1. Terms обязательны
        if (!$command->acceptTerms) {
            throw new UnprocessableEntityException(
                'Согласие на использование личных данных должно быть получено',
                ErrorCode::B_PERSONAL_DATA_CONSENT_REQUIRED
            );
        }

// 2. Маркетинг
        if ($command->acceptMarketing) {

            if (empty($command->acceptMarketingVer)) {
                throw new UnprocessableEntityException(
                    'Наличие версии согласия обязательно',
                    ErrorCode::B_MARKETING_VERSION_MISSING
                );
            }
        }

        // 5. Провера по словарям на данных из них
        $this->dictionaryDomainService->validateRoleCountry(
            $command->roleCode,
            $command->countryAlpha2
        );
    }
}

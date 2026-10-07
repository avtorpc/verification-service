<?php

namespace App\Application\Registration\QuickSignup\DTO;

final class QuickSignupRequest
{
    public function __construct(
        public string $roleCode,
        public string $countryAlpha2,
        public string $email,
        public string $acceptMarketingVer,
        public string $urlPageCheckout,
        public ?string $userAgent,
        public bool $acceptTerms,
        public bool $acceptMarketing,
    ) {}
}

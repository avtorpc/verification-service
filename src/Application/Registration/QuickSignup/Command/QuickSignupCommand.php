<?php

namespace App\Application\Registration\QuickSignup\Command;

class QuickSignupCommand
{
    public function __construct(
        public string $roleCode,
        public string $countryAlpha2,
        public string $email,
        public bool $acceptTerms,
        public ?bool $acceptMarketing = null,
        public ?string $acceptMarketingVer = null,
        public ?string $urlPageCheckout = null,
        public ?string $userAgent = null,
        public ?string $ipAddress = null
    ) {}
}

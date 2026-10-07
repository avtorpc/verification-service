<?php

namespace App\Application\Registration\QuickSignup\DTO;

/**
 * @psalm-suppress PossiblyUnusedProperty
 */
class QuickSignupRawRequest
{
    public mixed $roleCode;
    public mixed $countryAlpha2;
    public mixed $email;

    public mixed $acceptMarketingVer;
    public mixed $urlPageCheckout;

    public mixed $userAgent;

    public mixed $acceptTerms;
    public mixed $acceptMarketing;
}

<?php

namespace App\Application\Registration\QuickSignup\Mapper;

use App\Application\Registration\QuickSignup\DTO\QuickSignupRequest;
use App\Application\Registration\QuickSignup\Command\QuickSignupCommand;

class QuickSignupMapper
{
    public static function mapRequestToCommand(
        QuickSignupRequest $request,
        ?string $ipAddress = null
    ): QuickSignupCommand {
        return new QuickSignupCommand(
            roleCode: $request->roleCode,
            countryAlpha2: $request->countryAlpha2,
            email: $request->email,
            acceptTerms: $request->acceptTerms,
            acceptMarketing: $request->acceptMarketing,
            acceptMarketingVer: $request->acceptMarketingVer,
            urlPageCheckout: $request->urlPageCheckout,
            userAgent: $request->userAgent,
            ipAddress: $ipAddress
        );
    }
}

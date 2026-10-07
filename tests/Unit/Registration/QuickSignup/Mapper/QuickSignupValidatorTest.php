<?php

namespace App\Tests\Application\Registration\QuickSignup;

use App\Application\Registration\QuickSignup\Command\QuickSignupCommand;
use App\Application\Registration\QuickSignup\QuickSignupValidator;
use App\Domain\Dictionaries\DictionaryDomainService;
use App\Shared\Exception\ErrorCode;
use App\Shared\Exception\UnprocessableEntityException;
use PHPUnit\Framework\TestCase;

final class QuickSignupValidatorTest extends TestCase
{
    private function createCommand(
        bool $acceptTerms = true,
        bool $acceptMarketing = true,
        ?string $acceptMarketingVer = '0.1.1'
    ): QuickSignupCommand {
        return new QuickSignupCommand(
            roleCode: 'seller',
            countryAlpha2: 'DE',
            email: 'test@example.com',
            acceptTerms: $acceptTerms,
            acceptMarketing: $acceptMarketing,
            acceptMarketingVer: $acceptMarketingVer,
            urlPageCheckout: null,
            userAgent: null,
            ipAddress: null
        );
    }

    private function stubDomainService(): DictionaryDomainService
    {
        return $this->createStub(DictionaryDomainService::class);
    }

    private function mockDomainService(): DictionaryDomainService
    {
        $mock = $this->createMock(DictionaryDomainService::class);

        $mock->expects($this->once())
            ->method('validateRoleCountry')
            ->with('seller', 'DE');

        return $mock;
    }

    public function test_success(): void
    {
        $validator = new QuickSignupValidator(
            $this->mockDomainService()
        );

        $validator->validate($this->createCommand());

        $this->assertTrue(true);
    }

    public function test_terms_required(): void
    {
        $validator = new QuickSignupValidator($this->stubDomainService());

        $this->expectException(UnprocessableEntityException::class);

        $validator->validate(
            $this->createCommand(
                acceptTerms: false
            )
        );
    }

    public function test_marketing_required(): void
    {
        $validator = new QuickSignupValidator($this->stubDomainService());

        $this->expectException(UnprocessableEntityException::class);

        $validator->validate(
            $this->createCommand(
                acceptMarketing: false
            )
        );
    }

    public function test_marketing_version_required(): void
    {
        $validator = new QuickSignupValidator($this->stubDomainService());

        $this->expectException(UnprocessableEntityException::class);

        $validator->validate(
            $this->createCommand(
                acceptMarketing: true,
                acceptMarketingVer: null
            )
        );
    }
}

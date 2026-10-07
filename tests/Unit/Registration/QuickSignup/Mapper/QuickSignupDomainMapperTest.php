<?php

namespace App\Tests\Application\Registration\QuickSignup\Mapper;

use App\Application\Registration\QuickSignup\DTO\QuickSignupRawRequest;
use App\Application\Registration\QuickSignup\Mapper\QuickSignupDomainMapper;
use App\Application\Registration\QuickSignup\DTO\QuickSignupRequest;
use App\Shared\Exception\BadRequestException;
use App\Shared\Exception\ErrorCode;
use PHPUnit\Framework\TestCase;

final class QuickSignupDomainMapperTest extends TestCase
{
    private function valid(): QuickSignupRawRequest
    {
        $dto = new QuickSignupRawRequest();

        $dto->roleCode = 'seller';
        $dto->countryAlpha2 = 'ru';
        $dto->email = 'test@example.com';

        $dto->acceptMarketingVer = 'v1';
        $dto->urlPageCheckout = 'https://example.com/checkout';

        $dto->userAgent = 'Mozilla';

        $dto->acceptTerms = true;
        $dto->acceptMarketing = false;

        return $dto;
    }

    public function test_happy_path_mapping(): void
    {
        $result = QuickSignupDomainMapper::map($this->valid());

        $this->assertInstanceOf(QuickSignupRequest::class, $result);

        $this->assertSame('seller', $result->roleCode);
        $this->assertSame('RU', $result->countryAlpha2); // normalization
        $this->assertSame('test@example.com', $result->email);

        $this->assertSame('v1', $result->acceptMarketingVer);
        $this->assertSame('https://example.com/checkout', $result->urlPageCheckout);

        $this->assertSame('Mozilla', $result->userAgent);

        $this->assertTrue($result->acceptTerms);
        $this->assertFalse($result->acceptMarketing);
    }
}

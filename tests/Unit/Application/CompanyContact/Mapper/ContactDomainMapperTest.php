<?php

namespace App\Tests\Unit\Application\CompanyContact\Mapper;

use App\Application\CompanyContact\DTO\ContactRawRequest;
use App\Application\CompanyContact\Mapper\ContactDomainMapper;
use App\Shared\Exception\ValidationException;
use PHPUnit\Framework\TestCase;

class ContactDomainMapperTest extends TestCase
{
    private ContactDomainMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new ContactDomainMapper();
    }

    public function testMapValidData(): void
    {
        $raw = new ContactRawRequest(
            companyId: '1001',
            contactType: 'phone',
            value: '+7 (999) 123-45-67',
            isPrimary: '1',
        );

        $domain = $this->mapper->map($raw);

        self::assertSame(1001, $domain->companyId);
        self::assertSame('phone', $domain->contactType);
        self::assertSame('+7 (999) 123-45-67', $domain->value);
        self::assertTrue($domain->isPrimary);
    }

    public function testMapAllContactTypes(): void
    {
        $types = ['phone', 'email', 'website', 'telegram', 'whatsapp'];

        foreach ($types as $type) {
            $raw = new ContactRawRequest(
                companyId: '1001',
                contactType: $type,
                value: 'test',
            );

            $domain = $this->mapper->map($raw);
            self::assertSame($type, $domain->contactType);
        }
    }

    public function testMapMissingCompanyIdThrows(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('companyId is required');

        $raw = new ContactRawRequest(
            contactType: 'phone',
            value: '+7 (999) 123-45-67',
        );

        $this->mapper->map($raw);
    }

    public function testMapMissingContactTypeThrows(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('contactType is required');

        $raw = new ContactRawRequest(
            companyId: '1001',
            value: '+7 (999) 123-45-67',
        );

        $this->mapper->map($raw);
    }

    public function testMapMissingValueThrows(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('value is required');

        $raw = new ContactRawRequest(
            companyId: '1001',
            contactType: 'phone',
        );

        $this->mapper->map($raw);
    }

    public function testMapInvalidContactTypeThrows(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('contactType is required');

        $raw = new ContactRawRequest(
            companyId: '1001',
            contactType: 'skype',
            value: 'test',
        );

        $this->mapper->map($raw);
    }
}

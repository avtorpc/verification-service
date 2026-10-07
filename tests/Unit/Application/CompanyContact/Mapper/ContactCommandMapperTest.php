<?php

namespace App\Tests\Unit\Application\CompanyContact\Mapper;

use App\Application\CompanyContact\DTO\ContactDomainRequest;
use App\Application\CompanyContact\Mapper\ContactCommandMapper;
use PHPUnit\Framework\TestCase;

class ContactCommandMapperTest extends TestCase
{
    private ContactCommandMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new ContactCommandMapper();
    }

    public function testMapCreate(): void
    {
        $domain = new ContactDomainRequest(
            companyId: 1001,
            contactType: 'phone',
            value: '+7 (999) 123-45-67',
            isPrimary: true,
        );

        $command = $this->mapper->mapCreate($domain);

        self::assertSame(1001, $command->companyId);
        self::assertSame('phone', $command->contactType);
        self::assertSame('+7 (999) 123-45-67', $command->value);
        self::assertTrue($command->isPrimary);
    }

    public function testMapUpdate(): void
    {
        $domain = new ContactDomainRequest(
            companyId: 1001,
            contactType: 'email',
            value: 'test@example.com',
        );

        $command = $this->mapper->mapUpdate(42, $domain);

        self::assertSame(42, $command->id);
        self::assertSame('email', $command->contactType);
        self::assertNull($command->isPrimary);
    }
}

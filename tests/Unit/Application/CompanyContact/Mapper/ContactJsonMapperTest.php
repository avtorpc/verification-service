<?php

namespace App\Tests\Unit\Application\CompanyContact\Mapper;

use App\Application\CompanyContact\Mapper\ContactJsonMapper;
use PHPUnit\Framework\TestCase;

class ContactJsonMapperTest extends TestCase
{
    private ContactJsonMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new ContactJsonMapper();
    }

    public function testMapFullData(): void
    {
        $data = [
            'companyId' => 1001,
            'contactType' => 'phone',
            'value' => '+7 (999) 123-45-67',
            'isPrimary' => true,
        ];

        $raw = $this->mapper->map($data);

        self::assertSame('1001', $raw->companyId);
        self::assertSame('phone', $raw->contactType);
        self::assertSame('+7 (999) 123-45-67', $raw->value);
        self::assertSame('1', $raw->isPrimary);
    }

    public function testMapMinimalData(): void
    {
        $data = [
            'companyId' => 1001,
            'contactType' => 'email',
            'value' => 'test@example.com',
        ];

        $raw = $this->mapper->map($data);

        self::assertSame('1001', $raw->companyId);
        self::assertNull($raw->isPrimary);
    }
}

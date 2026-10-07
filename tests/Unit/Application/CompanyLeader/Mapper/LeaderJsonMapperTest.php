<?php

namespace App\Tests\Unit\Application\CompanyLeader\Mapper;

use App\Application\CompanyLeader\Mapper\LeaderJsonMapper;
use PHPUnit\Framework\TestCase;

class LeaderJsonMapperTest extends TestCase
{
    private LeaderJsonMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new LeaderJsonMapper();
    }

    public function testMapFullData(): void
    {
        $data = [
            'companyId' => 1001,
            'firstName' => 'Иван',
            'lastName' => 'Петров',
            'patronymic' => 'Сергеевич',
            'documentTypeCode' => 'passport',
        ];

        $raw = $this->mapper->map($data);

        self::assertSame('1001', $raw->companyId);
        self::assertSame('Иван', $raw->firstName);
        self::assertSame('Петров', $raw->lastName);
        self::assertSame('Сергеевич', $raw->patronymic);
        self::assertSame('passport', $raw->documentTypeCode);
    }

    public function testMapMinimalData(): void
    {
        $data = [
            'companyId' => 1001,
            'firstName' => 'Иван',
            'lastName' => 'Петров',
            'documentTypeCode' => 'passport',
        ];

        $raw = $this->mapper->map($data);

        self::assertSame('1001', $raw->companyId);
        self::assertSame('Иван', $raw->firstName);
        self::assertNull($raw->patronymic);
    }
}

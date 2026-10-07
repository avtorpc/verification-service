<?php

namespace App\Tests\Unit\Application\CompanyLeader\Mapper;

use App\Application\CompanyLeader\DTO\LeaderRawRequest;
use App\Application\CompanyLeader\Mapper\LeaderDomainMapper;
use App\Shared\Exception\ValidationException;
use PHPUnit\Framework\TestCase;

class LeaderDomainMapperTest extends TestCase
{
    private LeaderDomainMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new LeaderDomainMapper();
    }

    public function testMapValidData(): void
    {
        $raw = new LeaderRawRequest(
            companyId: '1001',
            firstName: 'Иван',
            lastName: 'Петров',
            patronymic: 'Сергеевич',
            documentTypeCode: 'passport',
        );

        $domain = $this->mapper->map($raw);

        self::assertSame(1001, $domain->companyId);
        self::assertSame('Иван', $domain->firstName);
        self::assertSame('Петров', $domain->lastName);
        self::assertSame('Сергеевич', $domain->patronymic);
        self::assertSame('passport', $domain->documentTypeCode);
    }

    public function testMapMinimalWithoutPatronymic(): void
    {
        $raw = new LeaderRawRequest(
            companyId: '1001',
            firstName: 'Иван',
            lastName: 'Петров',
            documentTypeCode: 'passport',
        );

        $domain = $this->mapper->map($raw);

        self::assertNull($domain->patronymic);
    }

    public function testMapMissingCompanyIdThrows(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('companyId is required');

        $raw = new LeaderRawRequest(
            firstName: 'Иван',
            lastName: 'Петров',
            documentTypeCode: 'passport',
        );

        $this->mapper->map($raw);
    }

    public function testMapMissingFirstNameThrows(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('firstName is required');

        $raw = new LeaderRawRequest(
            companyId: '1001',
            lastName: 'Петров',
            documentTypeCode: 'passport',
        );

        $this->mapper->map($raw);
    }

    public function testMapMissingLastNameThrows(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('lastName is required');

        $raw = new LeaderRawRequest(
            companyId: '1001',
            firstName: 'Иван',
            documentTypeCode: 'passport',
        );

        $this->mapper->map($raw);
    }

    public function testMapMissingDocumentTypeCodeThrows(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('documentTypeCode is required');

        $raw = new LeaderRawRequest(
            companyId: '1001',
            firstName: 'Иван',
            lastName: 'Петров',
        );

        $this->mapper->map($raw);
    }
}

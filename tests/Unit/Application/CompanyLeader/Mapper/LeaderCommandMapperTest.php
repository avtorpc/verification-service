<?php

namespace App\Tests\Unit\Application\CompanyLeader\Mapper;

use App\Application\CompanyLeader\DTO\LeaderDomainRequest;
use App\Application\CompanyLeader\Mapper\LeaderCommandMapper;
use PHPUnit\Framework\TestCase;

class LeaderCommandMapperTest extends TestCase
{
    private LeaderCommandMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new LeaderCommandMapper();
    }

    public function testMapCreate(): void
    {
        $domain = new LeaderDomainRequest(
            companyId: 1001,
            firstName: 'Иван',
            lastName: 'Петров',
            documentTypeCode: 'passport',
            patronymic: 'Сергеевич',
        );

        $command = $this->mapper->mapCreate($domain);

        self::assertSame(1001, $command->companyId);
        self::assertSame('Иван', $command->firstName);
        self::assertSame('Петров', $command->lastName);
        self::assertSame('Сергеевич', $command->patronymic);
        self::assertSame('passport', $command->documentTypeCode);
    }

    public function testMapUpdate(): void
    {
        $domain = new LeaderDomainRequest(
            companyId: 1001,
            firstName: 'Петр',
            lastName: 'Иванов',
            documentTypeCode: 'passport',
        );

        $command = $this->mapper->mapUpdate(42, $domain);

        self::assertSame(42, $command->id);
        self::assertSame('Петр', $command->firstName);
        self::assertNull($command->patronymic);
    }
}

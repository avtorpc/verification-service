<?php

namespace App\Tests\Unit\Application\Company\Mapper;

use App\Application\Company\DTO\CompanyDomainRequest;
use App\Application\Company\Mapper\CompanyCommandMapper;
use PHPUnit\Framework\TestCase;

class CompanyCommandMapperTest extends TestCase
{
    private CompanyCommandMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new CompanyCommandMapper();
    }

    public function testMapCreate(): void
    {
        $domain = new CompanyDomainRequest(
            legalEntity: '7712345678',
            legalFormName: 'ООО',
            companyName: 'ООО "ТехноПром"',
            countryCode: 'RU',
        );

        $command = $this->mapper->mapCreate($domain);

        self::assertSame('7712345678', $command->legalEntity);
        self::assertSame('ООО', $command->legalFormName);
        self::assertSame('ООО "ТехноПром"', $command->companyName);
        self::assertSame('RU', $command->countryCode);
        self::assertNull($command->roleCode);
    }

    public function testMapUpdate(): void
    {
        $domain = new CompanyDomainRequest(
            legalEntity: '7712345678',
            legalFormName: 'АО',
            companyName: 'Тест',
            countryCode: 'KZ',
        );

        $command = $this->mapper->mapUpdate(42, $domain);

        self::assertSame(42, $command->id);
        self::assertSame('7712345678', $command->legalEntity);
        self::assertSame('АО', $command->legalFormName);
    }
}

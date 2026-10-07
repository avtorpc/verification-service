<?php

namespace App\Tests\Unit\Infrastructure\Dictionaries;

use App\Infrastructure\Dictionaries\CountriesDictionary;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;

class CountriesDictionaryTest extends TestCase
{
    private Connection $connection;
    private CountriesDictionary $dictionary;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->dictionary = new CountriesDictionary($this->connection);
    }

    public function testGetName(): void
    {
        self::assertSame('countries', $this->dictionary->getName());
    }

    public function testGetItemsReturnsCountryDtos(): void
    {
        $this->connection
            ->expects(self::once())
            ->method('fetchAllAssociative')
            ->willReturn([
                [
                    'id' => 1,
                    'name' => 'Россия',
                    'short_name' => 'RU',
                    'name_genitive' => 'России',
                    'full_name' => 'Российская Федерация',
                    'alpha_2_code' => 'RU',
                    'alpha_3_code' => 'RUS',
                    'numeric_code' => 643,
                    'is_active' => true,
                ],
            ]);

        $items = $this->dictionary->getItems();

        self::assertCount(1, $items);
        self::assertSame('Россия', $items[0]->name);
        self::assertSame('RU', $items[0]->alpha2Code);
        self::assertTrue($items[0]->isActive);
    }

    public function testGetItemsOnlyActiveBuildsCorrectSql(): void
    {
        $this->connection
            ->expects(self::once())
            ->method('fetchAllAssociative')
            ->with(self::stringContains('WHERE is_active = true'))
            ->willReturn([]);

        $this->dictionary->getItems(true);
    }

    public function testGetItemsAllIncludesInactive(): void
    {
        $this->connection
            ->expects(self::once())
            ->method('fetchAllAssociative')
            ->with(self::logicalNot(self::stringContains('WHERE')))
            ->willReturn([]);

        $this->dictionary->getItems(false);
    }

    public function testToArrayOnCountryDto(): void
    {
        $this->connection
            ->method('fetchAllAssociative')
            ->willReturn([
                [
                    'id' => 1,
                    'name' => 'Россия',
                    'short_name' => 'RU',
                    'name_genitive' => 'России',
                    'full_name' => 'Российская Федерация',
                    'alpha_2_code' => 'RU',
                    'alpha_3_code' => 'RUS',
                    'numeric_code' => 643,
                    'is_active' => true,
                ],
            ]);

        $items = $this->dictionary->getItems();
        $arr = $items[0]->toArray();

        self::assertSame('Россия', $arr['name']);
        self::assertSame('RU', $arr['alpha_2_code']);
        self::assertTrue($arr['is_active']);
    }
}

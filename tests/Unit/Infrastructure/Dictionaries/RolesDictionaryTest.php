<?php

namespace App\Tests\Unit\Infrastructure\Dictionaries;

use App\Infrastructure\Dictionaries\RolesDictionary;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;

class RolesDictionaryTest extends TestCase
{
    private Connection $connection;
    private RolesDictionary $dictionary;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->dictionary = new RolesDictionary($this->connection);
    }

    public function testGetName(): void
    {
        self::assertSame('roles', $this->dictionary->getName());
    }

    public function testGetItemsReturnsRoleDtos(): void
    {
        $this->connection
            ->expects(self::once())
            ->method('fetchAllAssociative')
            ->willReturn([
                [
                    'id' => 1,
                    'code' => 'SELLER',
                    'name' => 'Продавец',
                    'name_genitive' => 'Продавца',
                    'is_active' => true,
                ],
            ]);

        $items = $this->dictionary->getItems();

        self::assertCount(1, $items);
        self::assertSame('SELLER', $items[0]->code);
        self::assertSame('Продавец', $items[0]->name);
        self::assertTrue($items[0]->isActive);
    }

    public function testToArrayOnRoleDto(): void
    {
        $this->connection
            ->method('fetchAllAssociative')
            ->willReturn([
                [
                    'id' => 1,
                    'code' => 'SELLER',
                    'name' => 'Продавец',
                    'name_genitive' => 'Продавца',
                    'is_active' => true,
                ],
            ]);

        $items = $this->dictionary->getItems();
        $arr = $items[0]->toArray();

        self::assertSame('SELLER', $arr['code']);
        self::assertSame('Продавец', $arr['name']);
        self::assertTrue($arr['is_active']);
    }
}

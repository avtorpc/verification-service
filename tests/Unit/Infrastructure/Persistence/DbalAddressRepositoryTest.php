<?php

namespace App\Tests\Unit\Infrastructure\Persistence;

use App\Domain\CompanyAddress\CompanyAddress;
use App\Infrastructure\Persistence\DbalAddressRepository;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;

class DbalAddressRepositoryTest extends TestCase
{
    private Connection $connection;
    private DbalAddressRepository $repository;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->repository = new DbalAddressRepository($this->connection, 'verification');
    }

    public function testInsertReturnsId(): void
    {
        $this->connection
            ->expects(self::once())
            ->method('fetchOne')
            ->willReturn('10');

        $id = $this->repository->insert([
            'company_id' => 1,
            'address_type' => 'legal',
            'country_code' => 'RU',
            'region' => 'Москва',
            'city' => 'Москва',
            'street' => 'ул. Тест',
            'house' => '1',
            'apartment' => null,
            'zip_code' => '125009',
            'is_same_as_legal' => null,
        ]);

        self::assertSame(10, $id);
    }

    public function testFindByIdReturnsAddress(): void
    {
        $this->connection
            ->expects(self::once())
            ->method('fetchAssociative')
            ->willReturn([
                'id' => 10,
                'company_id' => 1,
                'address_type' => 'legal',
                'country_code' => 'RU',
                'region' => 'Москва',
                'city' => 'Москва',
                'street' => 'ул. Тест',
                'house' => '1',
                'apartment' => null,
                'zip_code' => '125009',
                'is_same_as_legal' => null,
            ]);

        $address = $this->repository->findById(10);

        self::assertInstanceOf(CompanyAddress::class, $address);
        self::assertSame(10, $address->id);
        self::assertSame('legal', $address->addressType);
    }

    public function testFindByIdReturnsNull(): void
    {
        $this->connection
            ->expects(self::once())
            ->method('fetchAssociative')
            ->willReturn(false);

        self::assertNull($this->repository->findById(999));
    }

    public function testFindByCompanyIdReturnsArray(): void
    {
        $this->connection
            ->expects(self::once())
            ->method('fetchAllAssociative')
            ->willReturn([
                ['id' => 1, 'company_id' => 1, 'address_type' => 'legal', 'country_code' => 'RU', 'region' => 'Москва', 'city' => 'Москва', 'street' => 'ул. Тест', 'house' => '1', 'apartment' => null, 'zip_code' => '125009', 'is_same_as_legal' => null],
            ]);

        $addresses = $this->repository->findByCompanyId(1);

        self::assertCount(1, $addresses);
        self::assertInstanceOf(CompanyAddress::class, $addresses[0]);
    }
}

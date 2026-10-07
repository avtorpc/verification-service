<?php

namespace App\Tests\Unit\Infrastructure\Persistence;

use App\Domain\CompanyLeader\CompanyLeader;
use App\Infrastructure\Persistence\DbalLeaderRepository;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;

class DbalLeaderRepositoryTest extends TestCase
{
    private Connection $connection;
    private DbalLeaderRepository $repository;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->repository = new DbalLeaderRepository($this->connection, 'verification');
    }

    public function testInsertReturnsId(): void
    {
        $this->connection
            ->expects(self::once())
            ->method('fetchOne')
            ->willReturn('5');

        $id = $this->repository->insert([
            'company_id' => 1,
            'first_name' => 'Иван',
            'last_name' => 'Петров',
            'patronymic' => 'Сергеевич',
            'document_type_code' => 'passport',
        ]);

        self::assertSame(5, $id);
    }

    public function testFindByIdReturnsLeader(): void
    {
        $this->connection
            ->expects(self::once())
            ->method('fetchAssociative')
            ->willReturn([
                'id' => 5,
                'company_id' => 1,
                'first_name' => 'Иван',
                'last_name' => 'Петров',
                'patronymic' => 'Сергеевич',
                'document_type_code' => 'passport',
            ]);

        $leader = $this->repository->findById(5);

        self::assertInstanceOf(CompanyLeader::class, $leader);
        self::assertSame('Иван', $leader->firstName);
    }

    public function testFindByCompanyIdReturnsLeader(): void
    {
        $this->connection
            ->expects(self::once())
            ->method('fetchAssociative')
            ->willReturn([
                'id' => 5,
                'company_id' => 1,
                'first_name' => 'Иван',
                'last_name' => 'Петров',
                'patronymic' => null,
                'document_type_code' => 'passport',
            ]);

        $leader = $this->repository->findByCompanyId(1);

        self::assertInstanceOf(CompanyLeader::class, $leader);
        self::assertNull($leader->patronymic);
    }

    public function testFindByCompanyIdReturnsNull(): void
    {
        $this->connection
            ->expects(self::once())
            ->method('fetchAssociative')
            ->willReturn(false);

        self::assertNull($this->repository->findByCompanyId(999));
    }
}

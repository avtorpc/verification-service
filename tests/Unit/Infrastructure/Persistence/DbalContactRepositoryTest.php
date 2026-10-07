<?php

namespace App\Tests\Unit\Infrastructure\Persistence;

use App\Domain\CompanyContact\CompanyContact;
use App\Infrastructure\Persistence\DbalContactRepository;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;

class DbalContactRepositoryTest extends TestCase
{
    private Connection $connection;
    private DbalContactRepository $repository;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->repository = new DbalContactRepository($this->connection, 'verification');
    }

    public function testInsertReturnsId(): void
    {
        $this->connection
            ->expects(self::once())
            ->method('fetchOne')
            ->willReturn('7');

        $id = $this->repository->insert([
            'company_id' => 1,
            'contact_type' => 'phone',
            'value' => '+79991234567',
            'is_primary' => true,
        ]);

        self::assertSame(7, $id);
    }

    public function testFindByIdReturnsContact(): void
    {
        $this->connection
            ->expects(self::once())
            ->method('fetchAssociative')
            ->willReturn([
                'id' => 7,
                'company_id' => 1,
                'contact_type' => 'phone',
                'value' => '+79991234567',
                'is_primary' => true,
            ]);

        $contact = $this->repository->findById(7);

        self::assertInstanceOf(CompanyContact::class, $contact);
        self::assertSame('phone', $contact->contactType);
        self::assertTrue($contact->isPrimary);
    }

    public function testFindByCompanyIdReturnsArray(): void
    {
        $this->connection
            ->expects(self::once())
            ->method('fetchAllAssociative')
            ->willReturn([
                ['id' => 1, 'company_id' => 1, 'contact_type' => 'phone', 'value' => '+79991234567', 'is_primary' => true],
            ]);

        $contacts = $this->repository->findByCompanyId(1);

        self::assertCount(1, $contacts);
        self::assertInstanceOf(CompanyContact::class, $contacts[0]);
    }

    public function testFindByCompanyIdReturnsEmptyArray(): void
    {
        $this->connection
            ->expects(self::once())
            ->method('fetchAllAssociative')
            ->willReturn([]);

        self::assertSame([], $this->repository->findByCompanyId(999));
    }
}

<?php

namespace App\Tests\Unit\Infrastructure\Persistence;

use App\Domain\Company\Company;
use App\Infrastructure\Persistence\DbalCompanyRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use PHPUnit\Framework\TestCase;

class DbalCompanyRepositoryTest extends TestCase
{
    private Connection $connection;
    private DbalCompanyRepository $repository;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->repository = new DbalCompanyRepository($this->connection, 'verification');
    }

    public function testInsertReturnsId(): void
    {
        $this->connection
            ->expects(self::once())
            ->method('fetchOne')
            ->willReturn('42');

        $id = $this->repository->insert([
            'legal_entity' => '7712345678',
            'legal_form_name' => 'ООО',
            'company_name' => 'Тест',
            'role_code' => 'SELLER',
            'country_code' => 'RU',
            'status' => 'draft',
            'is_kz_nds_applicable' => null,
            'is_nds_payer' => null,
        ]);

        self::assertSame(42, $id);
    }

    public function testFindByIdReturnsCompany(): void
    {
        $this->connection
            ->expects(self::once())
            ->method('fetchAssociative')
            ->willReturn([
                'id' => 1,
                'legal_entity' => '7712345678',
                'legal_form_name' => 'ООО',
                'company_name' => 'Тест',
                'role_code' => 'SELLER',
                'country_code' => 'RU',
                'status' => 'draft',
                'is_kz_nds_applicable' => null,
                'is_nds_payer' => null,
                'user_uuid' => null,
                'created_at' => '2026-06-17T12:00:00Z',
                'updated_at' => '2026-06-17T12:00:00Z',
            ]);

        $company = $this->repository->findById(1);

        self::assertInstanceOf(Company::class, $company);
        self::assertSame(1, $company->id);
        self::assertSame('7712345678', $company->legalEntity);
    }

    public function testFindByIdReturnsNullWhenNotFound(): void
    {
        $this->connection
            ->expects(self::once())
            ->method('fetchAssociative')
            ->willReturn(false);

        self::assertNull($this->repository->findById(999));
    }

    public function testExistsByLegalEntityReturnsTrue(): void
    {
        $this->connection
            ->expects(self::once())
            ->method('fetchOne')
            ->willReturn('1');

        self::assertTrue($this->repository->existsByLegalEntity('7712345678'));
    }

    public function testExistsByLegalEntityReturnsFalse(): void
    {
        $this->connection
            ->expects(self::once())
            ->method('fetchOne')
            ->willReturn(false);

        self::assertFalse($this->repository->existsByLegalEntity('unknown'));
    }

    public function testUpdateExecutesStatement(): void
    {
        $this->connection
            ->expects(self::once())
            ->method('executeStatement')
            ->willReturn(1);

        $this->repository->update(1, [
            'legal_entity' => '7712345678',
            'legal_form_name' => 'ООО',
            'company_name' => 'Тест',
            'role_code' => 'SELLER',
            'country_code' => 'RU',
            'status' => 'approved',
            'is_kz_nds_applicable' => null,
            'is_nds_payer' => null,
        ]);

        // no exception = success
        self::assertTrue(true);
    }
}

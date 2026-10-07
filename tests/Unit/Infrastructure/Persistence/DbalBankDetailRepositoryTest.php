<?php

namespace App\Tests\Unit\Infrastructure\Persistence;

use App\Domain\CompanyBankDetail\CompanyBankDetail;
use App\Infrastructure\Persistence\DbalBankDetailRepository;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;

class DbalBankDetailRepositoryTest extends TestCase
{
    private Connection $connection;
    private DbalBankDetailRepository $repository;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->repository = new DbalBankDetailRepository($this->connection, 'verification');
    }

    public function testInsertReturnsId(): void
    {
        $this->connection
            ->expects(self::once())
            ->method('fetchOne')
            ->willReturn('3');

        $id = $this->repository->insert([
            'company_id' => 1,
            'account_number' => 'KZ123456789012345678',
            'bank_name' => 'Test Bank',
            'bik' => '044525187',
            'swift' => 'AAAABB22XXX',
            'correspondent_account' => 'CORR1234567890',
            'iban' => 'KZ123456789012345678',
            'country_code' => 'KZ',
        ]);

        self::assertSame(3, $id);
    }

    public function testFindByIdReturnsDetail(): void
    {
        $this->connection
            ->expects(self::once())
            ->method('fetchAssociative')
            ->willReturn([
                'id' => 3,
                'company_id' => 1,
                'account_number' => 'KZ123456789012345678',
                'bank_name' => 'Test Bank',
                'bik' => '044525187',
                'swift' => 'AAAABB22XXX',
                'correspondent_account' => 'CORR1234567890',
                'iban' => 'KZ123456789012345678',
                'country_code' => 'KZ',
            ]);

        $detail = $this->repository->findById(3);

        self::assertInstanceOf(CompanyBankDetail::class, $detail);
        self::assertSame('KZ123456789012345678', $detail->accountNumber);
    }

    public function testFindByCompanyIdReturnsDetail(): void
    {
        $this->connection
            ->expects(self::once())
            ->method('fetchAssociative')
            ->willReturn([
                'id' => 3,
                'company_id' => 1,
                'account_number' => 'KZ123456789012345678',
                'bank_name' => 'Test Bank',
                'bik' => '044525187',
                'swift' => 'AAAABB22XXX',
                'correspondent_account' => 'CORR1234567890',
                'iban' => 'KZ123456789012345678',
                'country_code' => 'KZ',
            ]);

        $detail = $this->repository->findByCompanyId(1);

        self::assertInstanceOf(CompanyBankDetail::class, $detail);
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

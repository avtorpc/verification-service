<?php

namespace App\Tests\Unit\Application\CompanyContact\Service;

use App\Application\CompanyContact\Command\CreateContactCommand;
use App\Application\CompanyContact\Command\UpdateContactCommand;
use App\Application\CompanyContact\Service\ContactService;
use App\Domain\Company\Company;
use App\Domain\CompanyContact\CompanyContact;
use App\Infrastructure\Persistence\DbalCompanyRepository;
use App\Infrastructure\Persistence\DbalContactRepository;
use App\Shared\Exception\NotFoundException;
use PHPUnit\Framework\TestCase;

class ContactServiceTest extends TestCase
{
    private DbalContactRepository $contactRepository;
    private DbalCompanyRepository $companyRepository;
    private ContactService $service;

    protected function setUp(): void
    {
        $this->contactRepository = $this->createMock(DbalContactRepository::class);
        $this->companyRepository = $this->createMock(DbalCompanyRepository::class);
        $this->service = new ContactService($this->contactRepository, $this->companyRepository);
    }

    public function testCreateSuccess(): void
    {
        $this->companyRepository
            ->expects(self::once())
            ->method('findById')
            ->with(1001)
            ->willReturn(Company::fromDatabaseRow(['id' => 1001, 'legal_entity' => '7712345678', 'legal_form_name' => 'ООО', 'company_name' => 'Тест', 'country_code' => 'RU', 'status' => 'draft', 'role_code' => null, 'is_kz_nds_applicable' => null, 'is_nds_payer' => null, 'user_uuid' => null, 'created_at' => '2026-01-01T00:00:00Z', 'updated_at' => '2026-01-01T00:00:00Z']));

        $this->contactRepository
            ->expects(self::once())
            ->method('insert')
            ->willReturn(42);

        $this->contactRepository
            ->expects(self::once())
            ->method('findById')
            ->with(42)
            ->willReturn(CompanyContact::fromDatabaseRow([
                'id' => 42,
                'company_id' => 1001,
                'contact_type' => 'phone',
                'value' => '+7 (999) 123-45-67',
                'is_primary' => true,
            ]));

        $command = new CreateContactCommand(
            companyId: 1001,
            contactType: 'phone',
            value: '+7 (999) 123-45-67',
            isPrimary: true,
        );

        $contact = $this->service->create($command);

        self::assertSame(42, $contact->id);
        self::assertSame('phone', $contact->contactType);
    }

    public function testCreateCompanyNotFoundThrows(): void
    {
        $this->companyRepository
            ->expects(self::once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('not found');

        $command = new CreateContactCommand(
            companyId: 999,
            contactType: 'email',
            value: 'test@example.com',
        );

        $this->service->create($command);
    }

    public function testUpdateNotFound(): void
    {
        $this->contactRepository
            ->expects(self::once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Contact with id 999 not found');

        $command = new UpdateContactCommand(
            id: 999,
            companyId: 1001,
            contactType: 'email',
            value: 'test@example.com',
        );

        $this->service->update($command);
    }
}

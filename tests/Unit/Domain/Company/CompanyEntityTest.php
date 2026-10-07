<?php

namespace App\Tests\Unit\Domain\Company;

use App\Domain\Company\Company;
use App\Domain\CompanyAddress\CompanyAddress;
use App\Domain\CompanyLeader\CompanyLeader;
use App\Domain\CompanyContact\CompanyContact;
use App\Domain\CompanyBankDetail\CompanyBankDetail;
use PHPUnit\Framework\TestCase;

class CompanyEntityTest extends TestCase
{
    public function testCompanyFromDatabaseRow(): void
    {
        $company = Company::fromDatabaseRow([
            'id' => 42,
            'legal_entity' => '7712345678',
            'legal_form_name' => 'ООО',
            'company_name' => 'Тест',
            'role_code' => 'SELLER',
            'country_code' => 'RU',
            'status' => 'draft',
            'is_kz_nds_applicable' => null,
            'is_nds_payer' => true,
            'user_uuid' => null,
            'created_at' => '2026-06-17T12:00:00Z',
            'updated_at' => '2026-06-17T12:00:00Z',
        ]);

        self::assertSame(42, $company->id);
        self::assertSame('7712345678', $company->legalEntity);
        self::assertSame('ООО', $company->legalFormName);
        self::assertSame('Тест', $company->companyName);
        self::assertSame('SELLER', $company->roleCode);
        self::assertSame('RU', $company->countryCode);
        self::assertSame('draft', $company->status);
        self::assertNull($company->isKzNdsApplicable);
        self::assertTrue($company->isNdsPayer);
        self::assertNull($company->userUuid);
        self::assertInstanceOf(\DateTimeImmutable::class, $company->createdAt);
        self::assertInstanceOf(\DateTimeImmutable::class, $company->updatedAt);
    }

    public function testCompanyAddressFromDatabaseRow(): void
    {
        $address = CompanyAddress::fromDatabaseRow([
            'id' => 10,
            'company_id' => 42,
            'address_type' => 'legal',
            'country_code' => 'RU',
            'region' => 'Московская область',
            'city' => 'Москва',
            'street' => 'ул. Ленина',
            'house' => '15',
            'apartment' => null,
            'zip_code' => '125009',
            'is_same_as_legal' => null,
        ]);

        self::assertSame(10, $address->id);
        self::assertSame(42, $address->companyId);
        self::assertSame('legal', $address->addressType);
        self::assertSame('RU', $address->countryCode);
        self::assertNull($address->apartment);
        self::assertNull($address->isSameAsLegal);
    }

    public function testCompanyLeaderFromDatabaseRow(): void
    {
        $leader = CompanyLeader::fromDatabaseRow([
            'id' => 5,
            'company_id' => 42,
            'first_name' => 'Иван',
            'last_name' => 'Петров',
            'patronymic' => 'Сергеевич',
            'document_type_code' => 'passport',
        ]);

        self::assertSame(5, $leader->id);
        self::assertSame(42, $leader->companyId);
        self::assertSame('Иван', $leader->firstName);
        self::assertSame('Петров', $leader->lastName);
        self::assertSame('Сергеевич', $leader->patronymic);
        self::assertSame('passport', $leader->documentTypeCode);
    }

    public function testCompanyContactFromDatabaseRow(): void
    {
        $contact = CompanyContact::fromDatabaseRow([
            'id' => 7,
            'company_id' => 42,
            'contact_type' => 'phone',
            'value' => '+79991234567',
            'is_primary' => true,
        ]);

        self::assertSame(7, $contact->id);
        self::assertSame(42, $contact->companyId);
        self::assertSame('phone', $contact->contactType);
        self::assertSame('+79991234567', $contact->value);
        self::assertTrue($contact->isPrimary);
    }

    public function testCompanyBankDetailFromDatabaseRow(): void
    {
        $detail = CompanyBankDetail::fromDatabaseRow([
            'id' => 3,
            'company_id' => 42,
            'account_number' => 'KZ123456789012345678',
            'bank_name' => 'Test Bank',
            'bik' => '044525187',
            'swift' => 'AAAABB22XXX',
            'correspondent_account' => 'CORR1234567890',
            'iban' => 'KZ123456789012345678',
            'country_code' => 'KZ',
        ]);

        self::assertSame(3, $detail->id);
        self::assertSame(42, $detail->companyId);
        self::assertSame('KZ123456789012345678', $detail->accountNumber);
        self::assertSame('AAAABB22XXX', $detail->swift);
        self::assertSame('KZ123456789012345678', $detail->iban);
    }
}

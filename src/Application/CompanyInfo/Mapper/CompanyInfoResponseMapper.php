<?php

namespace App\Application\CompanyInfo\Mapper;

use App\Domain\Company\Company;
use App\Domain\CompanyAddress\CompanyAddress;
use App\Domain\CompanyBankDetail\CompanyBankDetail;
use App\Domain\CompanyContact\CompanyContact;
use App\Domain\CompanyLeader\CompanyLeader;
use App\Domain\Registration\CompanyRegistrationProgress;

final class CompanyInfoResponseMapper
{
    /**
     * @param CompanyAddress[] $addresses
     * @param CompanyContact[] $contacts
     */
    public function map(
        Company $company,
        array $addresses,
        ?CompanyLeader $leader,
        array $contacts,
        ?CompanyBankDetail $bankDetail,
        ?CompanyRegistrationProgress $progress,
    ): array {
        return [
            'company' => $this->mapCompany($company),
            'addresses' => array_map(fn(CompanyAddress $address) => $this->mapAddress($address), $addresses),
            'leader' => $leader !== null ? $this->mapLeader($leader) : null,
            'contacts' => array_map(fn(CompanyContact $contact) => $this->mapContact($contact), $contacts),
            'bankDetails' => $bankDetail !== null ? $this->mapBankDetail($bankDetail) : null,
            'progress' => $progress !== null ? $this->mapProgress($progress) : null,
        ];
    }

    private function mapCompany(Company $company): array
    {
        return [
            'id' => $company->id,
            'legalEntity' => $company->legalEntity,
            'legalFormName' => $company->legalFormName,
            'companyName' => $company->companyName,
            'roleCode' => $company->roleCode,
            'countryCode' => $company->countryCode,
            'status' => $company->status,
            'isKzNdsApplicable' => $company->isKzNdsApplicable,
            'isNdsPayer' => $company->isNdsPayer,
            'userUuid' => $company->userUuid,
            'createdAt' => $company->createdAt->format('Y-m-d\TH:i:s\Z'),
            'updatedAt' => $company->updatedAt->format('Y-m-d\TH:i:s\Z'),
        ];
    }

    private function mapAddress(CompanyAddress $address): array
    {
        return [
            'id' => $address->id,
            'companyId' => $address->companyId,
            'addressType' => $address->addressType,
            'countryCode' => $address->countryCode,
            'region' => $address->region,
            'city' => $address->city,
            'street' => $address->street,
            'house' => $address->house,
            'apartment' => $address->apartment,
            'zipCode' => $address->zipCode,
            'isSameAsLegal' => $address->isSameAsLegal,
        ];
    }

    private function mapLeader(CompanyLeader $leader): array
    {
        return [
            'id' => $leader->id,
            'companyId' => $leader->companyId,
            'firstName' => $leader->firstName,
            'lastName' => $leader->lastName,
            'patronymic' => $leader->patronymic,
            'documentTypeCode' => $leader->documentTypeCode,
        ];
    }

    private function mapContact(CompanyContact $contact): array
    {
        return [
            'id' => $contact->id,
            'companyId' => $contact->companyId,
            'contactType' => $contact->contactType,
            'value' => $contact->value,
            'isPrimary' => $contact->isPrimary,
        ];
    }

    private function mapBankDetail(CompanyBankDetail $bankDetail): array
    {
        return [
            'id' => $bankDetail->id,
            'companyId' => $bankDetail->companyId,
            'accountNumber' => $bankDetail->accountNumber,
            'bankName' => $bankDetail->bankName,
            'bik' => $bankDetail->bik,
            'swift' => $bankDetail->swift,
            'correspondentAccount' => $bankDetail->correspondentAccount,
            'iban' => $bankDetail->iban,
            'countryCode' => $bankDetail->countryCode,
        ];
    }

    private function mapProgress(CompanyRegistrationProgress $progress): array
    {
        return [
            'id' => $progress->id,
            'companyId' => $progress->companyId,
            'currentStep' => $progress->currentStep->value,
            'completedSteps' => array_map(fn($step) => $step->value, $progress->completedSteps),
            'status' => $progress->status,
            'traceId' => $progress->traceId,
            'createdAt' => $progress->createdAt->format('Y-m-d\TH:i:s\Z'),
            'updatedAt' => $progress->updatedAt->format('Y-m-d\TH:i:s\Z'),
        ];
    }
}

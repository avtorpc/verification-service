<?php

namespace App\Application\CompanyBankDetail\Mapper;

use App\Application\CompanyBankDetail\Command\CreateBankDetailCommand;
use App\Application\CompanyBankDetail\Command\UpdateBankDetailCommand;
use App\Application\CompanyBankDetail\DTO\BankDetailDomainRequest;

class BankDetailCommandMapper
{
    public function mapCreate(BankDetailDomainRequest $domain): CreateBankDetailCommand
    {
        return new CreateBankDetailCommand(
            companyId: $domain->companyId,
            accountNumber: $domain->accountNumber,
            bankName: $domain->bankName,
            bik: $domain->bik,
            swift: $domain->swift,
            correspondentAccount: $domain->correspondentAccount,
            iban: $domain->iban,
            countryCode: $domain->countryCode,
        );
    }

    public function mapUpdate(int $id, BankDetailDomainRequest $domain): UpdateBankDetailCommand
    {
        return new UpdateBankDetailCommand(
            id: $id,
            companyId: $domain->companyId,
            accountNumber: $domain->accountNumber,
            bankName: $domain->bankName,
            bik: $domain->bik,
            swift: $domain->swift,
            correspondentAccount: $domain->correspondentAccount,
            iban: $domain->iban,
            countryCode: $domain->countryCode,
        );
    }
}

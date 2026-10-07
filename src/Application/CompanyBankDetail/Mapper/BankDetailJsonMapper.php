<?php

namespace App\Application\CompanyBankDetail\Mapper;

use App\Application\CompanyBankDetail\DTO\BankDetailRawRequest;

class BankDetailJsonMapper
{
    public function map(array $data): BankDetailRawRequest
    {
        return new BankDetailRawRequest(
            companyId: isset($data['companyId']) ? (string) $data['companyId'] : null,
            accountNumber: $data['accountNumber'] ?? null,
            bankName: $data['bankName'] ?? null,
            bik: $data['bik'] ?? null,
            swift: $data['swift'] ?? null,
            correspondentAccount: $data['correspondentAccount'] ?? null,
            iban: $data['iban'] ?? null,
            countryCode: $data['countryCode'] ?? null,
        );
    }
}

<?php

namespace App\Application\CompanyBankDetail\DTO;

class BankDetailDomainRequest
{
    public function __construct(
        public readonly int $companyId,
        public readonly string $accountNumber,
        public readonly string $bankName,
        public readonly string $bik,
        public readonly string $swift,
        public readonly string $correspondentAccount,
        public readonly string $iban,
        public readonly string $countryCode,
    ) {
    }
}

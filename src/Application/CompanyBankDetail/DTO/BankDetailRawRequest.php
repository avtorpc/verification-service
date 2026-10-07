<?php

namespace App\Application\CompanyBankDetail\DTO;

class BankDetailRawRequest
{
    public function __construct(
        public readonly ?string $companyId = null,
        public readonly ?string $accountNumber = null,
        public readonly ?string $bankName = null,
        public readonly ?string $bik = null,
        public readonly ?string $swift = null,
        public readonly ?string $correspondentAccount = null,
        public readonly ?string $iban = null,
        public readonly ?string $countryCode = null,
    ) {
    }
}

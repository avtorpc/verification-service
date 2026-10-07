<?php

namespace App\Domain\CompanyBankDetail;

class CompanyBankDetail
{
    public function __construct(
        public readonly int $id,
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

    public static function fromDatabaseRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            companyId: (int) $row['company_id'],
            accountNumber: $row['account_number'],
            bankName: $row['bank_name'],
            bik: $row['bik'],
            swift: $row['swift'],
            correspondentAccount: $row['correspondent_account'],
            iban: $row['iban'],
            countryCode: $row['country_code'],
        );
    }
}

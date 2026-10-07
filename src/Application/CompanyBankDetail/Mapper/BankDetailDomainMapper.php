<?php

namespace App\Application\CompanyBankDetail\Mapper;

use App\Application\CompanyBankDetail\DTO\BankDetailDomainRequest;
use App\Application\CompanyBankDetail\DTO\BankDetailRawRequest;
use App\Shared\Exception\ValidationException;

class BankDetailDomainMapper
{
    public function map(BankDetailRawRequest $raw): BankDetailDomainRequest
    {
        $errors = [];

        if ($raw->companyId === null || !ctype_digit($raw->companyId)) {
            $errors[] = 'companyId is required and must be an integer';
        }

        if ($raw->accountNumber === null || trim($raw->accountNumber) === '') {
            $errors[] = 'accountNumber is required';
        }

        if ($raw->bankName === null || trim($raw->bankName) === '') {
            $errors[] = 'bankName is required';
        }

        if ($raw->bik === null || trim($raw->bik) === '') {
            $errors[] = 'bik is required';
        }

        if ($raw->swift === null || trim($raw->swift) === '') {
            $errors[] = 'swift is required';
        }

        if ($raw->correspondentAccount === null || trim($raw->correspondentAccount) === '') {
            $errors[] = 'correspondentAccount is required';
        }

        if ($raw->iban === null || trim($raw->iban) === '') {
            $errors[] = 'iban is required';
        }

        if ($raw->countryCode === null || trim($raw->countryCode) === '') {
            $errors[] = 'countryCode is required';
        }

        if (!empty($errors)) {
            throw new ValidationException(implode('; ', $errors));
        }

        return new BankDetailDomainRequest(
            companyId: (int) $raw->companyId,
            accountNumber: trim($raw->accountNumber),
            bankName: trim($raw->bankName),
            bik: trim($raw->bik),
            swift: trim($raw->swift),
            correspondentAccount: trim($raw->correspondentAccount),
            iban: trim($raw->iban),
            countryCode: trim($raw->countryCode),
        );
    }
}

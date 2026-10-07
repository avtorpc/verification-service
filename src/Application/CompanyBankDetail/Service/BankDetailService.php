<?php

namespace App\Application\CompanyBankDetail\Service;

use App\Application\CompanyBankDetail\Command\CreateBankDetailCommand;
use App\Application\CompanyBankDetail\Command\UpdateBankDetailCommand;
use App\Domain\CompanyBankDetail\CompanyBankDetail;
use App\Infrastructure\Persistence\DbalBankDetailRepository;
use App\Infrastructure\Persistence\DbalCompanyRepository;
use App\Shared\Exception\NotFoundException;

class BankDetailService
{
    public function __construct(
        private DbalBankDetailRepository $repository,
        private DbalCompanyRepository $companyRepository,
    ) {
    }

    public function create(CreateBankDetailCommand $command): CompanyBankDetail
    {
        $company = $this->companyRepository->findById($command->companyId);
        if ($company === null) {
            throw new NotFoundException(
                sprintf('Company with id %d not found', $command->companyId)
            );
        }

        $id = $this->repository->insert([
            'company_id' => $command->companyId,
            'account_number' => $command->accountNumber,
            'bank_name' => $command->bankName,
            'bik' => $command->bik,
            'swift' => $command->swift,
            'correspondent_account' => $command->correspondentAccount,
            'iban' => $command->iban,
            'country_code' => $command->countryCode,
        ]);

        return $this->repository->findById($id);
    }

    public function update(UpdateBankDetailCommand $command): CompanyBankDetail
    {
        $existing = $this->repository->findById($command->id);
        if ($existing === null) {
            throw new NotFoundException(
                sprintf('Bank detail with id %d not found', $command->id)
            );
        }

        $company = $this->companyRepository->findById($command->companyId);
        if ($company === null) {
            throw new NotFoundException(
                sprintf('Company with id %d not found', $command->companyId)
            );
        }

        $this->repository->update($command->id, [
            'company_id' => $command->companyId,
            'account_number' => $command->accountNumber,
            'bank_name' => $command->bankName,
            'bik' => $command->bik,
            'swift' => $command->swift,
            'correspondent_account' => $command->correspondentAccount,
            'iban' => $command->iban,
            'country_code' => $command->countryCode,
        ]);

        return $this->repository->findById($command->id);
    }
}

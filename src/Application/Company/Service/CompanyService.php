<?php

namespace App\Application\Company\Service;

use App\Application\Company\Command\CreateCompanyCommand;
use App\Application\Company\Command\UpdateCompanyCommand;
use App\Domain\Company\Company;
use App\Infrastructure\Persistence\DbalCompanyRepository;
use App\Shared\Exception\DuplicateException;
use App\Shared\Exception\NotFoundException;

class CompanyService
{
    public function __construct(
        private DbalCompanyRepository $repository,
    ) {
    }

    public function create(CreateCompanyCommand $command): Company
    {
        if ($this->repository->existsByLegalEntity($command->legalEntity)) {
            throw new DuplicateException(
                sprintf('Company with legalEntity "%s" already exists', $command->legalEntity)
            );
        }

        $id = $this->repository->insert([
            'legal_entity' => $command->legalEntity,
            'legal_form_name' => $command->legalFormName,
            'company_name' => $command->companyName,
            'role_code' => $command->roleCode,
            'country_code' => $command->countryCode,
            'status' => $command->status,
            'is_kz_nds_applicable' => $command->isKzNdsApplicable,
            'is_nds_payer' => $command->isNdsPayer,
        ]);

        return $this->repository->findById($id);
    }

    public function update(UpdateCompanyCommand $command): Company
    {
        $existing = $this->repository->findById($command->id);
        if ($existing === null) {
            throw new NotFoundException(
                sprintf('Company with id %d not found', $command->id)
            );
        }

        if ($this->repository->existsByLegalEntity($command->legalEntity, $command->id)) {
            throw new DuplicateException(
                sprintf('Company with legalEntity "%s" already exists', $command->legalEntity)
            );
        }

        $this->repository->update($command->id, [
            'legal_entity' => $command->legalEntity,
            'legal_form_name' => $command->legalFormName,
            'company_name' => $command->companyName,
            'role_code' => $command->roleCode,
            'country_code' => $command->countryCode,
            'status' => $command->status,
            'is_kz_nds_applicable' => $command->isKzNdsApplicable,
            'is_nds_payer' => $command->isNdsPayer,
        ]);

        return $this->repository->findById($command->id);
    }

    public function sendToModeration(int $companyId): Company
    {
        $company = $this->repository->findById($companyId);
        if ($company === null) {
            throw new NotFoundException(
                sprintf('Company with id %d not found', $companyId)
            );
        }

        $this->repository->update($companyId, [
            'legal_entity' => $company->legalEntity,
            'legal_form_name' => $company->legalFormName,
            'company_name' => $company->companyName,
            'role_code' => $company->roleCode,
            'country_code' => $company->countryCode,
            'status' => 'moderation',
            'is_kz_nds_applicable' => $company->isKzNdsApplicable,
            'is_nds_payer' => $company->isNdsPayer,
        ]);

        return $this->repository->findById($companyId);
    }
}

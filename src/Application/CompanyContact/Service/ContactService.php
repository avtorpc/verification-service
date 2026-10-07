<?php

namespace App\Application\CompanyContact\Service;

use App\Application\CompanyContact\Command\CreateContactCommand;
use App\Application\CompanyContact\Command\UpdateContactCommand;
use App\Domain\CompanyContact\CompanyContact;
use App\Infrastructure\Persistence\DbalCompanyRepository;
use App\Infrastructure\Persistence\DbalContactRepository;
use App\Shared\Exception\NotFoundException;

class ContactService
{
    public function __construct(
        private DbalContactRepository $repository,
        private DbalCompanyRepository $companyRepository,
    ) {
    }

    public function create(CreateContactCommand $command): CompanyContact
    {
        $company = $this->companyRepository->findById($command->companyId);
        if ($company === null) {
            throw new NotFoundException(
                sprintf('Company with id %d not found', $command->companyId)
            );
        }

        $id = $this->repository->insert([
            'company_id' => $command->companyId,
            'contact_type' => $command->contactType,
            'value' => $command->value,
            'is_primary' => $command->isPrimary,
        ]);

        return $this->repository->findById($id);
    }

    public function update(UpdateContactCommand $command): CompanyContact
    {
        $existing = $this->repository->findById($command->id);
        if ($existing === null) {
            throw new NotFoundException(
                sprintf('Contact with id %d not found', $command->id)
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
            'contact_type' => $command->contactType,
            'value' => $command->value,
            'is_primary' => $command->isPrimary,
        ]);

        return $this->repository->findById($command->id);
    }
}

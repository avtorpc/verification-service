<?php

namespace App\Application\CompanyLeader\Service;

use App\Application\CompanyLeader\Command\CreateLeaderCommand;
use App\Application\CompanyLeader\Command\UpdateLeaderCommand;
use App\Domain\CompanyLeader\CompanyLeader;
use App\Infrastructure\Persistence\DbalCompanyRepository;
use App\Infrastructure\Persistence\DbalLeaderRepository;
use App\Shared\Exception\NotFoundException;

class LeaderService
{
    public function __construct(
        private DbalLeaderRepository $repository,
        private DbalCompanyRepository $companyRepository,
    ) {
    }

    public function create(CreateLeaderCommand $command): CompanyLeader
    {
        $company = $this->companyRepository->findById($command->companyId);
        if ($company === null) {
            throw new NotFoundException(
                sprintf('Company with id %d not found', $command->companyId)
            );
        }

        $id = $this->repository->insert([
            'company_id' => $command->companyId,
            'first_name' => $command->firstName,
            'last_name' => $command->lastName,
            'patronymic' => $command->patronymic,
            'document_type_code' => $command->documentTypeCode,
        ]);

        return $this->repository->findById($id);
    }

    public function update(UpdateLeaderCommand $command): CompanyLeader
    {
        $existing = $this->repository->findById($command->id);
        if ($existing === null) {
            throw new NotFoundException(
                sprintf('Leader with id %d not found', $command->id)
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
            'first_name' => $command->firstName,
            'last_name' => $command->lastName,
            'patronymic' => $command->patronymic,
            'document_type_code' => $command->documentTypeCode,
        ]);

        return $this->repository->findById($command->id);
    }
}

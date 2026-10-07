<?php

namespace App\Application\CompanyAddress\Service;

use App\Application\CompanyAddress\Command\CreateAddressCommand;
use App\Application\CompanyAddress\Command\UpdateAddressCommand;
use App\Domain\CompanyAddress\CompanyAddress;
use App\Infrastructure\Persistence\DbalAddressRepository;
use App\Infrastructure\Persistence\DbalCompanyRepository;
use App\Shared\Exception\NotFoundException;

class AddressService
{
    public function __construct(
        private DbalAddressRepository $repository,
        private DbalCompanyRepository $companyRepository,
    ) {
    }

    public function create(CreateAddressCommand $command): CompanyAddress
    {
        $company = $this->companyRepository->findById($command->companyId);
        if ($company === null) {
            throw new NotFoundException(
                sprintf('Company with id %d not found', $command->companyId)
            );
        }

        $id = $this->repository->insert([
            'company_id' => $command->companyId,
            'address_type' => $command->addressType,
            'country_code' => $command->countryCode,
            'region' => $command->region,
            'city' => $command->city,
            'street' => $command->street,
            'house' => $command->house,
            'apartment' => $command->apartment,
            'zip_code' => $command->zipCode,
            'is_same_as_legal' => $command->isSameAsLegal,
        ]);

        return $this->repository->findById($id);
    }

    public function update(UpdateAddressCommand $command): CompanyAddress
    {
        $existing = $this->repository->findById($command->id);
        if ($existing === null) {
            throw new NotFoundException(
                sprintf('Address with id %d not found', $command->id)
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
            'address_type' => $command->addressType,
            'country_code' => $command->countryCode,
            'region' => $command->region,
            'city' => $command->city,
            'street' => $command->street,
            'house' => $command->house,
            'apartment' => $command->apartment,
            'zip_code' => $command->zipCode,
            'is_same_as_legal' => $command->isSameAsLegal,
        ]);

        return $this->repository->findById($command->id);
    }
}

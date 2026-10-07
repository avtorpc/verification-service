<?php

namespace App\Application\CompanyInfo;

use App\Application\CompanyInfo\Command\GetCompanyInfoCommand;
use App\Application\CompanyInfo\DTO\GetCompanyInfoResponse;
use App\Application\CompanyInfo\Mapper\CompanyInfoResponseMapper;
use App\Infrastructure\Persistence\DbalAddressRepository;
use App\Infrastructure\Persistence\DbalBankDetailRepository;
use App\Infrastructure\Persistence\DbalCompanyRepository;
use App\Infrastructure\Persistence\DbalContactRepository;
use App\Infrastructure\Persistence\DbalLeaderRepository;
use App\Infrastructure\Persistence\Registration\DbalCompanyRegistrationProgressRepository;
use App\Shared\Exception\NotFoundException;
use App\Shared\Exception\ValidationException;
use App\Shared\Time\ClockInterface;

final class GetCompanyInfoHandler
{
    public function __construct(
        private DbalCompanyRepository $companyRepository,
        private DbalAddressRepository $addressRepository,
        private DbalLeaderRepository $leaderRepository,
        private DbalContactRepository $contactRepository,
        private DbalBankDetailRepository $bankDetailRepository,
        private DbalCompanyRegistrationProgressRepository $progressRepository,
        private CompanyInfoResponseMapper $responseMapper,
        private ClockInterface $clock,
    ) {}

    public function handle(GetCompanyInfoCommand $command): GetCompanyInfoResponse
    {
        if ($command->requestUserUuid !== $command->tokenUserUuid) {
            throw new ValidationException('uuid does not match access token subject');
        }

        $company = $this->companyRepository->findById($command->companyId);

        if ($company === null) {
            throw new NotFoundException("Company {$command->companyId} not found");
        }

        if ($company->userUuid === null || strtolower($company->userUuid) !== $command->tokenUserUuid) {
            throw new ValidationException('company does not belong to access token subject');
        }

        $data = $this->responseMapper->map(
            company: $company,
            addresses: $this->addressRepository->findByCompanyId($command->companyId),
            leader: $this->leaderRepository->findByCompanyId($command->companyId),
            contacts: $this->contactRepository->findByCompanyId($command->companyId),
            bankDetail: $this->bankDetailRepository->findByCompanyId($command->companyId),
            progress: $this->progressRepository->findByCompanyId($command->companyId),
        );

        return new GetCompanyInfoResponse(
            companyInfo: $data,
            clock: $this->clock
        );
    }
}

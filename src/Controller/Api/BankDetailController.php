<?php

namespace App\Controller\Api;

use App\Application\CompanyBankDetail\Mapper\BankDetailCommandMapper;
use App\Application\CompanyBankDetail\Mapper\BankDetailDomainMapper;
use App\Application\CompanyBankDetail\Mapper\BankDetailJsonMapper;
use App\Application\CompanyBankDetail\Service\BankDetailService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/company/bank-detail')]
class BankDetailController extends AbstractController
{
    public function __construct(
        private BankDetailJsonMapper $jsonMapper,
        private BankDetailDomainMapper $domainMapper,
        private BankDetailCommandMapper $commandMapper,
        private BankDetailService $bankDetailService,
    ) {
    }

    #[Route('', name: 'bank_detail_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $raw = $this->jsonMapper->map($request->toArray());
        $domain = $this->domainMapper->map($raw);
        $command = $this->commandMapper->mapCreate($domain);
        $bankDetail = $this->bankDetailService->create($command);

        return new JsonResponse($this->formatResponse($bankDetail, 'Запись банковских реквизитов компании создана'), 201);
    }

    #[Route('/{id}', name: 'bank_detail_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $raw = $this->jsonMapper->map($request->toArray());
        $domain = $this->domainMapper->map($raw);
        $command = $this->commandMapper->mapUpdate($id, $domain);
        $bankDetail = $this->bankDetailService->update($command);

        return new JsonResponse($this->formatResponse($bankDetail, 'Запись банковских реквизитов компании изменена'));
    }

    private function formatResponse(\App\Domain\CompanyBankDetail\CompanyBankDetail $detail, string $message): array
    {
        return [
            'success' => true,
            'timestamp' => (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d\TH:i:s.v\Z'),
            'message' => $message,
            'expiresInSeconds' => 600,
            'data' => [
                'id' => $detail->id,
                'companyId' => $detail->companyId,
                'accountNumber' => $detail->accountNumber,
                'bankName' => $detail->bankName,
                'bik' => $detail->bik,
                'swift' => $detail->swift,
                'correspondentAccount' => $detail->correspondentAccount,
                'iban' => $detail->iban,
                'countryCode' => $detail->countryCode,
            ],
        ];
    }
}

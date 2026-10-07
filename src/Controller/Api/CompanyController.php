<?php

namespace App\Controller\Api;

use App\Application\Company\Mapper\CompanyCommandMapper;
use App\Application\Company\Mapper\CompanyDomainMapper;
use App\Application\Company\Mapper\CompanyJsonMapper;
use App\Application\Company\Service\CompanyService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/company')]
class CompanyController extends AbstractController
{
    public function __construct(
        private CompanyJsonMapper $jsonMapper,
        private CompanyDomainMapper $domainMapper,
        private CompanyCommandMapper $commandMapper,
        private CompanyService $companyService,
    ) {
    }

    #[Route('', name: 'company_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $raw = $this->jsonMapper->map($request->toArray());
        $domain = $this->domainMapper->map($raw);
        $command = $this->commandMapper->mapCreate($domain);
        $company = $this->companyService->create($command);

        return new JsonResponse($this->formatResponse($company, 'Компания создана'), 201);
    }

    #[Route('/{id}', name: 'company_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $raw = $this->jsonMapper->map($request->toArray());
        $domain = $this->domainMapper->map($raw);
        $command = $this->commandMapper->mapUpdate($id, $domain);
        $company = $this->companyService->update($command);

        return new JsonResponse($this->formatResponse($company, 'Компания обновлена'));
    }

    #[Route('/{companyId}/moderation', name: 'company_moderation', methods: ['POST'])]
    public function sendToModeration(int $companyId): JsonResponse
    {
        $company = $this->companyService->sendToModeration($companyId);

        return new JsonResponse($this->formatResponse($company, 'Компания отправлена на модерацию'));
    }

    private function formatResponse(\App\Domain\Company\Company $company, string $message): array
    {
        return [
            'success' => true,
            'timestamp' => (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d\TH:i:s.v\Z'),
            'message' => $message,
            'expiresInSeconds' => 600,
            'data' => [
                'id' => $company->id,
                'legalEntity' => $company->legalEntity,
                'legalFormName' => $company->legalFormName,
                'companyName' => $company->companyName,
                'roleCode' => $company->roleCode,
                'countryCode' => $company->countryCode,
                'status' => $company->status,
                'isKzNdsApplicable' => $company->isKzNdsApplicable,
                'isNdsPayer' => $company->isNdsPayer,
                'createdAt' => $company->createdAt->format('Y-m-d\TH:i:s\Z'),
                'updatedAt' => $company->updatedAt->format('Y-m-d\TH:i:s\Z'),
            ],
        ];
    }
}

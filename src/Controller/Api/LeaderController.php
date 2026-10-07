<?php

namespace App\Controller\Api;

use App\Application\CompanyLeader\Mapper\LeaderCommandMapper;
use App\Application\CompanyLeader\Mapper\LeaderDomainMapper;
use App\Application\CompanyLeader\Mapper\LeaderJsonMapper;
use App\Application\CompanyLeader\Service\LeaderService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/company/leader')]
class LeaderController extends AbstractController
{
    public function __construct(
        private LeaderJsonMapper $jsonMapper,
        private LeaderDomainMapper $domainMapper,
        private LeaderCommandMapper $commandMapper,
        private LeaderService $leaderService,
    ) {
    }

    #[Route('', name: 'leader_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $raw = $this->jsonMapper->map($request->toArray());
        $domain = $this->domainMapper->map($raw);
        $command = $this->commandMapper->mapCreate($domain);
        $leader = $this->leaderService->create($command);

        return new JsonResponse($this->formatResponse($leader, 'Запись руководителя компании создана'), 201);
    }

    #[Route('/{id}', name: 'leader_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $raw = $this->jsonMapper->map($request->toArray());
        $domain = $this->domainMapper->map($raw);
        $command = $this->commandMapper->mapUpdate($id, $domain);
        $leader = $this->leaderService->update($command);

        return new JsonResponse($this->formatResponse($leader, 'Запись руководителя компании обновлена'));
    }

    private function formatResponse(\App\Domain\CompanyLeader\CompanyLeader $leader, string $message): array
    {
        return [
            'success' => true,
            'timestamp' => (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d\TH:i:s.v\Z'),
            'message' => $message,
            'expiresInSeconds' => 600,
            'data' => [
                'id' => $leader->id,
                'companyId' => $leader->companyId,
                'firstName' => $leader->firstName,
                'lastName' => $leader->lastName,
                'patronymic' => $leader->patronymic,
                'documentTypeCode' => $leader->documentTypeCode,
            ],
        ];
    }
}

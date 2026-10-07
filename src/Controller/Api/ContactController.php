<?php

namespace App\Controller\Api;

use App\Application\CompanyContact\Mapper\ContactCommandMapper;
use App\Application\CompanyContact\Mapper\ContactDomainMapper;
use App\Application\CompanyContact\Mapper\ContactJsonMapper;
use App\Application\CompanyContact\Service\ContactService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/company/contact')]
class ContactController extends AbstractController
{
    public function __construct(
        private ContactJsonMapper $jsonMapper,
        private ContactDomainMapper $domainMapper,
        private ContactCommandMapper $commandMapper,
        private ContactService $contactService,
    ) {
    }

    #[Route('', name: 'contact_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $raw = $this->jsonMapper->map($request->toArray());
        $domain = $this->domainMapper->map($raw);
        $command = $this->commandMapper->mapCreate($domain);
        $contact = $this->contactService->create($command);

        return new JsonResponse($this->formatResponse($contact, 'Запись контакта компании создана'), 201);
    }

    #[Route('/{id}', name: 'contact_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $raw = $this->jsonMapper->map($request->toArray());
        $domain = $this->domainMapper->map($raw);
        $command = $this->commandMapper->mapUpdate($id, $domain);
        $contact = $this->contactService->update($command);

        return new JsonResponse($this->formatResponse($contact, 'Запись контакта компании обновлена'));
    }

    private function formatResponse(\App\Domain\CompanyContact\CompanyContact $contact, string $message): array
    {
        return [
            'success' => true,
            'timestamp' => (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d\TH:i:s.v\Z'),
            'message' => $message,
            'expiresInSeconds' => 600,
            'data' => [
                'id' => $contact->id,
                'companyId' => $contact->companyId,
                'contactType' => $contact->contactType,
                'value' => $contact->value,
                'isPrimary' => $contact->isPrimary,
            ],
        ];
    }
}

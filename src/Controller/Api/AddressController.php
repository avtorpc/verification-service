<?php

namespace App\Controller\Api;

use App\Application\CompanyAddress\Mapper\AddressCommandMapper;
use App\Application\CompanyAddress\Mapper\AddressDomainMapper;
use App\Application\CompanyAddress\Mapper\AddressJsonMapper;
use App\Application\CompanyAddress\Service\AddressService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/company/addresses')]
class AddressController extends AbstractController
{
    public function __construct(
        private AddressJsonMapper $jsonMapper,
        private AddressDomainMapper $domainMapper,
        private AddressCommandMapper $commandMapper,
        private AddressService $addressService,
    ) {
    }

    #[Route('', name: 'address_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $raw = $this->jsonMapper->map($request->toArray());
        $domain = $this->domainMapper->map($raw);
        $command = $this->commandMapper->mapCreate($domain);
        $address = $this->addressService->create($command);

        return new JsonResponse($this->formatResponse($address, 'Запись адреса компании создана'), 201);
    }

    #[Route('/{id}', name: 'address_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $raw = $this->jsonMapper->map($request->toArray());
        $domain = $this->domainMapper->map($raw);
        $command = $this->commandMapper->mapUpdate($id, $domain);
        $address = $this->addressService->update($command);

        return new JsonResponse($this->formatResponse($address, 'Запись адреса компании обновлена'));
    }

    private function formatResponse(\App\Domain\CompanyAddress\CompanyAddress $address, string $message): array
    {
        return [
            'success' => true,
            'timestamp' => (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d\TH:i:s.v\Z'),
            'message' => $message,
            'expiresInSeconds' => 600,
            'data' => [
                'id' => $address->id,
                'companyId' => $address->companyId,
                'addressType' => $address->addressType,
                'countryCode' => $address->countryCode,
                'region' => $address->region,
                'city' => $address->city,
                'street' => $address->street,
                'house' => $address->house,
                'apartment' => $address->apartment,
                'zipCode' => $address->zipCode,
                'isSameAsLegal' => $address->isSameAsLegal,
            ],
        ];
    }
}

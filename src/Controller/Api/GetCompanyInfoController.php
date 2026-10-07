<?php

namespace App\Controller\Api;

use App\Application\CompanyInfo\GetCompanyInfoHandler;
use App\Application\CompanyInfo\Mapper\GetCompanyInfoCommandMapper;
use App\Application\CompanyInfo\Mapper\GetCompanyInfoDomainMapper;
use App\Application\CompanyInfo\Mapper\GetCompanyInfoJsonMapper;
use App\Infrastructure\Security\JwtAuthenticator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class GetCompanyInfoController
{
    public function __construct(
        private JwtAuthenticator $jwtAuthenticator,
        private GetCompanyInfoHandler $handler,
    ) {}

    #[Route('/get-company-info', name: 'registration_get_company_info', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $auth = $this->jwtAuthenticator->authenticate($request);

        $raw = GetCompanyInfoJsonMapper::fromJson($request->getContent());
        $dto = GetCompanyInfoDomainMapper::map($raw);
        $command = GetCompanyInfoCommandMapper::mapRequestToCommand(
            $dto,
            $auth->sub
        );

        $response = $this->handler->handle($command);

        return new JsonResponse($response->toArray());
    }
}

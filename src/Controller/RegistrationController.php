<?php

namespace App\Controller;

use App\Application\Registration\Mapper\RegistrationDomainMapper;
use App\Application\Registration\Mapper\RegistrationJsonMapper;
use App\Application\Registration\Service\RegistrationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    public function __construct(
        private RegistrationJsonMapper $jsonMapper,
        private RegistrationDomainMapper $domainMapper,
        private RegistrationService $registrationService,
    ) {
    }

    #[Route("/verify", name: "api_verify", methods: ["POST"])]
    public function verify(Request $request): JsonResponse
    {
        $raw = $this->jsonMapper->map($request->toArray());
        $domain = $this->domainMapper->map($raw);
        $result = $this->registrationService->complete($domain);

        return new JsonResponse($result);
    }
}

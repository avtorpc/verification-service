<?php

namespace App\Controller\Api\ResendCode;

use App\Application\Registration\ResendCode\Mapper\ResendCodeJsonMapper;
use App\Application\Registration\ResendCode\Mapper\ResendCodeDomainMapper;
use App\Application\Registration\ResendCode\Mapper\ResendCodeMapper;
use App\Application\Registration\ResendCode\ResendCodeHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use App\Infrastructure\Http\ClientIpResolver;
use Symfony\Component\Routing\Annotation\Route;

class ResendCodeController
{
    public function __construct(
        private ClientIpResolver $clientIpResolver,
        private ResendCodeHandler $handler,
    ) {
    }

    #[Route('/resend-code-email', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $raw = ResendCodeJsonMapper::fromJson(
            $request->getContent()
        );

        $dto = ResendCodeDomainMapper::map($raw);

        $command = ResendCodeMapper::mapRequestToCommand(
            $dto,
            $this->clientIpResolver->resolve($request)
        );

        $response = $this->handler->handle($command);

        return new JsonResponse(
            $response->toArray()
        );
    }
}

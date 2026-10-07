<?php

namespace App\Controller\Api\ResendCode;

use App\Application\Registration\ResendSmsCode\Mapper\ResendSmsCodeCommandMapper;
use App\Application\Registration\ResendSmsCode\Mapper\ResendSmsCodeDomainMapper;
use App\Application\Registration\ResendSmsCode\Mapper\ResendSmsCodeJsonMapper;
use App\Application\Registration\ResendSmsCode\ResendSmsCodeHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use App\Infrastructure\Http\ClientIpResolver;
use Symfony\Component\Routing\Annotation\Route;

final class ResendSmsCodeController
{
    public function __construct(
        private ClientIpResolver $clientIpResolver,
        private ResendSmsCodeHandler $handler,
    ) {}

    #[Route('/resend-code-sms', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $raw = ResendSmsCodeJsonMapper::fromJson($request->getContent());
        $dto = ResendSmsCodeDomainMapper::map($raw);

        $command = ResendSmsCodeCommandMapper::mapRequestToCommand(
            $dto,
            $this->clientIpResolver->resolve($request)
        );

        $response = $this->handler->handle($command);

        return new JsonResponse($response->toArray());
    }
}

<?php

namespace App\Controller\Api\SendCodeChannel;

use App\Application\Registration\QuickSignup\DTO\RequestContext;
use App\Application\Registration\VerificationPHONE\Mapper\VerificationCodeDomainMapper;
use App\Application\Registration\VerificationPHONE\Mapper\VerificationCodeJsonMapper;
use App\Application\Registration\VerificationPHONE\Mapper\VerificationCodeMapper;
use App\Application\Registration\VerificationPHONE\SendPhoneVerificationCodeHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use App\Infrastructure\Http\ClientIpResolver;
use Symfony\Component\Routing\Annotation\Route;


/**
 * Второй шаг решитстрации - передаем личные данные телефон и ИНН компании
 */
class SendPhoneCodeController extends AbstractController
{
    public function __construct(
        private ClientIpResolver $clientIpResolver,
        private SendPhoneVerificationCodeHandler $handler,
    ) {}

    #[Route('/send-code-channel', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        /**
         * 1. JSON → RAW DTO (без типизации, только extraction)
         */
        $raw = VerificationCodeJsonMapper::fromJson($request->getContent());

        /**
         * 2. RAW → CLEAN DTO (type casting + validation rules at domain level)
         */
        $dto = VerificationCodeDomainMapper::map($raw);

        /**
         * 3. CLEAN DTO → COMMAND
         */
        $command = VerificationCodeMapper::mapRequestToCommand($dto);

        /**
         * 4. BUSINESS LOGIC
         */
        $context = new RequestContext(
            ip: $this->clientIpResolver->resolve($request),
            uri: $request->getRequestUri(),
            method: $request->getMethod(),
            userAgent: $request->headers->get('User-Agent'),
        );

        $response = $this->handler->handle($command, $context);

        return new JsonResponse(
            $response->toArray()
        );
    }
}

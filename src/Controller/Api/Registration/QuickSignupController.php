<?php

namespace App\Controller\Api\Registration;

use App\Application\Registration\QuickSignup\DTO\RequestContext;
use App\Application\Registration\QuickSignup\Mapper\QuickSignupJsonMapper;
use App\Application\Registration\QuickSignup\Mapper\QuickSignupDomainMapper;
use App\Application\Registration\QuickSignup\Mapper\QuickSignupMapper;
use App\Application\Registration\QuickSignup\QuickSignupHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use App\Infrastructure\Http\ClientIpResolver;
use Symfony\Component\Routing\Annotation\Route;


/**
 * Контроллер начала регистрации
 * Пользователь вводит email
 * Получает проверочный код на свою почту
 */
class QuickSignupController
{
    public function __construct(
        private ClientIpResolver $clientIpResolver,
        private QuickSignupHandler $handler,
    ) {}

    #[Route('/quick-signup', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        /**
         * 1. JSON → RAW DTO (без типов, только extraction)
         */
        $raw = QuickSignupJsonMapper::fromJson($request->getContent());

        /**
         * 2. RAW → CLEAN DTO (type casting + controlled errors)
         */
        $dto = QuickSignupDomainMapper::map($raw);

        /**
         * 3. CLEAN DTO → COMMAND (твоя существующая логика)
         */
        $command = QuickSignupMapper::mapRequestToCommand(
            $dto,
            $this->clientIpResolver->resolve($request)
        );

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

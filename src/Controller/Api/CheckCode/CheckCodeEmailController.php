<?php

namespace App\Controller\Api\CheckCode;

use App\Application\Registration\Verification\Mapper\VerificationCodeJsonMapper;
use App\Application\Registration\Verification\Mapper\VerificationCodeDomainMapper;
use App\Application\Registration\Verification\Mapper\VerificationCodeMapper;
use App\Application\Registration\Verification\VerificationCodeEmailHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class CheckCodeEmailController extends AbstractController
{
    public function __construct(
        private VerificationCodeEmailHandler $handler,
    ) {}

    #[Route('/check-code-email', methods: ['POST'])]
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
        $response = $this->handler->handle($command);

        return new JsonResponse(
            $response->toArray()
        );
    }
}

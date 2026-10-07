<?php

namespace App\Controller;

use App\Infrastructure\Application\ApplicationInfo;
use App\Shared\Time\ClockInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class ApiController extends AbstractController
{
    public function __construct(
        private ApplicationInfo $app,
        private ClockInterface $clock
    ) {}

    #[Route("/", name: "app_api", methods: ["GET"])]
    public function index(): JsonResponse
    {
        return $this->json([
            'service' => $this->app->name(),
            'version' => $this->app->version(),
            'status' => 'ok',
            'timestamp' => $this->clock->nowFormatted()
        ]);
    }
}

<?php

namespace App\Tests\Unit\Controller\Api;

use App\Controller\ApiController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\JsonResponse;

class ApiControllerTest extends TestCase
{
    private function createController(): ApiController
    {
        $controller = new ApiController();
        $container = new ContainerBuilder();
        $container->set('serializer', new class {
            public function serialize(mixed $data, string $format): string
            {
                return json_encode($data);
            }
        });
        $controller->setContainer($container);

        return $controller;
    }

    public function testIndexReturnsServiceInfo(): void
    {
        $controller = $this->createController();
        $response = $controller->index();
        $data = json_decode($response->getContent(), true);

        self::assertSame('verification-service', $data['service']);
        self::assertSame('1.0.0', $data['version']);
        self::assertSame('ok', $data['status']);
        self::assertArrayHasKey('timestamp', $data);
    }

    public function testStatusReturnsHealthy(): void
    {
        $controller = $this->createController();
        $response = $controller->status();
        $data = json_decode($response->getContent(), true);

        self::assertSame('healthy', $data['status']);
        self::assertSame('verification-service', $data['service']);
        self::assertArrayHasKey('timestamp', $data);
    }
}

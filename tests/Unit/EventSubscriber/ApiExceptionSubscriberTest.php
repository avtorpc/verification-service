<?php

namespace App\Tests\Unit\EventSubscriber;

use App\Shared\Exception\DictionaryNotFoundException;
use App\EventSubscriber\ApiExceptionSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class ApiExceptionSubscriberTest extends TestCase
{
    private ApiExceptionSubscriber $subscriber;
    private HttpKernelInterface $kernel;

    protected function setUp(): void
    {
        $this->subscriber = new ApiExceptionSubscriber();
        $this->kernel = $this->createMock(HttpKernelInterface::class);
    }

    public function testSubscribedEvents(): void
    {
        self::assertArrayHasKey('kernel.exception', ApiExceptionSubscriber::getSubscribedEvents());
    }

    public function testDictionaryNotFoundReturns404(): void
    {
        $exception = new DictionaryNotFoundException('countries');
        $event = new ExceptionEvent($this->kernel, new Request(), HttpKernelInterface::MAIN_REQUEST, $exception);

        $this->subscriber->onKernelException($event);

        $response = $event->getResponse();
        self::assertSame(404, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        self::assertFalse($data['success']);
        self::assertSame('NOT_FOUND', $data['error']['code']);
    }

    public function testNotFoundHttpExceptionReturns404(): void
    {
        $exception = new NotFoundHttpException('Route not found');
        $event = new ExceptionEvent($this->kernel, new Request(), HttpKernelInterface::MAIN_REQUEST, $exception);

        $this->subscriber->onKernelException($event);

        $response = $event->getResponse();
        self::assertSame(404, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        self::assertFalse($data['success']);
        self::assertSame('ROUTE_NOT_FOUND', $data['error']['code']);
    }

    public function testAccessDeniedReturns403(): void
    {
        $exception = new AccessDeniedHttpException('Access denied');
        $event = new ExceptionEvent($this->kernel, new Request(), HttpKernelInterface::MAIN_REQUEST, $exception);

        $this->subscriber->onKernelException($event);

        $response = $event->getResponse();
        self::assertSame(403, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        self::assertFalse($data['success']);
        self::assertSame('HTTP_ERROR', $data['error']['code']);
    }

    public function testGenericExceptionReturns500(): void
    {
        $exception = new \RuntimeException('Something went wrong');
        $event = new ExceptionEvent($this->kernel, new Request(), HttpKernelInterface::MAIN_REQUEST, $exception);

        $this->subscriber->onKernelException($event);

        $response = $event->getResponse();
        self::assertSame(500, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        self::assertFalse($data['success']);
        self::assertSame('INTERNAL_ERROR', $data['error']['code']);
    }

    public function testPdoExceptionReturnsDatabaseError(): void
    {
        $exception = new \PDOException('Connection failed');
        $event = new ExceptionEvent($this->kernel, new Request(), HttpKernelInterface::MAIN_REQUEST, $exception);

        $this->subscriber->onKernelException($event);

        $response = $event->getResponse();
        self::assertSame(500, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        self::assertFalse($data['success']);
        self::assertSame('DATABASE_ERROR', $data['error']['code']);
    }

    public function testMethodNotAllowedReturns405(): void
    {
        $exception = new MethodNotAllowedHttpException(['GET'], 'Method not allowed');
        $event = new ExceptionEvent($this->kernel, new Request(), HttpKernelInterface::MAIN_REQUEST, $exception);

        $this->subscriber->onKernelException($event);

        $response = $event->getResponse();
        self::assertSame(405, $response->getStatusCode());
    }
}

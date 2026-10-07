<?php
namespace App\EventSubscriber;

use App\Application\Dictionaries\DictionaryNotFoundException;
use App\Shared\Exception\DuplicateException;
use App\Shared\Exception\InvalidTokenException;
use App\Shared\Exception\NotFoundException;
use App\Shared\Exception\ValidationException;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => ['onKernelException', 10]];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        $status = JsonResponse::HTTP_INTERNAL_SERVER_ERROR;
        $code = 'INTERNAL_ERROR';
        $message = 'Внутренняя ошибка сервера';

        switch (true) {
            case $exception instanceof ValidationException:
            case $exception instanceof InvalidTokenException:
                $status = JsonResponse::HTTP_UNPROCESSABLE_ENTITY;
                $code = $exception->getErrorCode();
                $message = $exception->getMessage();
                break;

            case $exception instanceof NotFoundException:
                $status = JsonResponse::HTTP_NOT_FOUND;
                $code = $exception->getErrorCode();
                $message = $exception->getMessage();
                break;

            case $exception instanceof DuplicateException:
                $status = JsonResponse::HTTP_CONFLICT;
                $code = $exception->getErrorCode();
                $message = $exception->getMessage();
                break;

            case $exception instanceof DictionaryNotFoundException:
                $status = JsonResponse::HTTP_NOT_FOUND;
                $code = 'NOT_FOUND';
                $message = $exception->getMessage();
                break;

            case $exception instanceof NotFoundHttpException:
                $status = JsonResponse::HTTP_NOT_FOUND;
                $code = 'ROUTE_NOT_FOUND';
                $message = 'API маршрут не найден';
                break;

            case $exception instanceof HttpExceptionInterface:
                $status = $exception->getStatusCode();
                $code = 'HTTP_ERROR';
                $message = $exception->getMessage();
                break;

            case $exception instanceof \PDOException:
            case $exception instanceof \Doctrine\DBAL\Exception:
                $status = JsonResponse::HTTP_INTERNAL_SERVER_ERROR;
                $code = 'DATABASE_ERROR';
                $message = 'Ошибка соединения с базой данных';
                break;

            default:
                $status = JsonResponse::HTTP_INTERNAL_SERVER_ERROR;
                $code = 'INTERNAL_ERROR';
                $message = $exception->getMessage();
        }

        $response = new JsonResponse([
            'success' => false,
            'timestamp' => (new \DateTimeImmutable())->format('Y-m-d\TH:i:s.v\Z'),
            'error' => [
                'code' => $code,
                'message' => $message,
            ]
        ], $status);

        $event->setResponse($response);
    }
}

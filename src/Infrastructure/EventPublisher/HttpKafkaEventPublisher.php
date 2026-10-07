<?php

namespace App\Infrastructure\EventPublisher;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class HttpKafkaEventPublisher
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private CreateNewUserEventSerializer $serializer,
        private string $baseUrl,
        private string $publishPath,
    ) {}

    private function buildUrl(): string
    {
        return rtrim($this->baseUrl, '/')
            . '/'
            . ltrim($this->publishPath, '/');
    }

    public function publish(object $event): void
    {
        $payload = $this->serializer->toArray($event);

        $response = $this->httpClient->request(
            'POST',
            $this->buildUrl(),
            [
                'json' => $payload,
                'timeout' => 3, // время ожидания ответа от клиента кафка
            ]
        );

        if ($response->getStatusCode() >= 300) {
            throw new \RuntimeException(
                sprintf(
                    'Kafka gateway error. HTTP status: %s',
                    $response->getStatusCode()
                )
            );
        }
    }
}

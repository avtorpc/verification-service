<?php

namespace App\Infrastructure\EventPublisher;

use App\Application\Shared\EventPublisherInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class HttpEventPublisher implements EventPublisherInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $baseUrl,
        private string $mailSendPath,
    ) {
    }

    public function publish(array $event): void
    {
        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($this->mailSendPath, '/');

        $response = $this->httpClient->request('POST', $url, [
            'json' => $event,
            'timeout' => 3,
        ]);

        $status = $response->getStatusCode();

        if ($status >= 300) {
            throw new \RuntimeException(
                'Event gateway error (HTTP ' . $status . ')'
            );
        }
    }
}

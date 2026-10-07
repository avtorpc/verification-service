<?php

namespace App\Tests\Unit\Infrastructure\EventPublisher;

use App\Infrastructure\EventPublisher\HttpEventPublisher;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class HttpEventPublisherTest extends TestCase
{
    private HttpClientInterface $httpClient;
    private HttpEventPublisher $publisher;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->publisher = new HttpEventPublisher(
            $this->httpClient,
            'http://nginx/event/',
            'kafka/mail-send',
        );
    }

    public function testPublishSendsPostToGateway(): void
    {
        $event = ['event_id' => 'test-uuid', 'event_type' => 'company.registration.completed'];

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);

        $this->httpClient
            ->expects(self::once())
            ->method('request')
            ->with('POST', 'http://nginx/event/kafka/mail-send', [
                'json' => $event,
                'timeout' => 3,
            ])
            ->willReturn($response);

        $this->publisher->publish($event);
    }

    public function testPublishThrowsOnNonSuccess(): void
    {
        $event = ['event' => 'test'];

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(502);

        $this->httpClient
            ->method('request')
            ->willReturn($response);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Event gateway error (HTTP 502)');

        $this->publisher->publish($event);
    }
}

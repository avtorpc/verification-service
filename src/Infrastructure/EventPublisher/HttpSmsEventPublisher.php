<?php

namespace App\Infrastructure\EventPublisher;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class HttpSmsEventPublisher
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private SmsEventSerializer $serializer,
        private string $baseUrl,
        private string $smsSendPath
    ) {}

    private function buildUrl(): string
    {
        $url = rtrim($this->baseUrl, '/')
            . '/'
            . ltrim($this->smsSendPath, '/');

        error_log('[SMS_EVENT][BUILD] baseUrl=' . $this->baseUrl);
        error_log('[SMS_EVENT][BUILD] smsSendPath=' . $this->smsSendPath);
        error_log('[SMS_EVENT][BUILD] finalUrl=' . $url);

        return $url;
    }

    public function publish($event): void
    {
        $payload = $this->serializer->toArray($event);
        $url = $this->buildUrl();

        error_log('[SMS_EVENT][REQUEST] START');
        error_log('[SMS_EVENT][REQUEST] url=' . $url);
        error_log('[SMS_EVENT][REQUEST] method=POST');
        error_log('[SMS_EVENT][REQUEST] payload=' . json_encode($payload));

        $start = microtime(true);

        try {
            $response = $this->httpClient->request('POST', $url, [
                'json' => $payload,
                'timeout' => 3,
            ]);

            $status = $response->getStatusCode();
            $body = $response->getContent(false);

            error_log('[SMS_EVENT][RESPONSE] status=' . $status);
            error_log('[SMS_EVENT][RESPONSE] body=' . $body);
            error_log('[SMS_EVENT][TIME] ms=' . ((microtime(true) - $start) * 1000));

            if ($status >= 300) {
                error_log('[SMS_EVENT][ERROR] gateway_status=' . $status);
                error_log('[SMS_EVENT][ERROR] gateway_body=' . $body);

                throw new \RuntimeException(
                    'SMS Event gateway error (HTTP ' . $status . ')'
                );
            }

            error_log('[SMS_EVENT][REQUEST] SUCCESS');

        } catch (\Throwable $e) {
            error_log('[SMS_EVENT][EXCEPTION] class=' . get_class($e));
            error_log('[SMS_EVENT][EXCEPTION] message=' . $e->getMessage());
            error_log('[SMS_EVENT][EXCEPTION] url=' . $url);

            throw $e;
        }
    }
}

<?php

namespace App\Application\Registration\Step\Service;

use App\Domain\Company\Company;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CompanyRegistrationBitrixNotifier
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $bitrixServiceUrl,
    ) {}

    public function notify(Company $company): void
    {
        $now = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d\TH:i:s.v\Z');

        $body = [
            'event_id' => $this->generateUuidV4(),
            'event_type' => 'company.registration.completed',
            'event_version' => 1,
            'occurred_at' => $now,
            'producer' => [
                'service' => 'mp-core',
            ],
            'payload' => [
                'user_id' => $company->legalEntity,
                'action' => 'create',
                'user_data' => [
                    'company_id' => $company->id,
                    'legal_entity' => $company->legalEntity,
                    'company_name' => $company->companyName,
                    'legal_form_name' => $company->legalFormName,
                    'role_code' => $company->roleCode,
                    'country_code' => $company->countryCode,
                    'status' => $company->status,
                ],
            ],
        ];

        $url = rtrim($this->bitrixServiceUrl, '/') . '/mock/bitrix/webhook/registration';

        $response = $this->httpClient->request('POST', $url, [
            'json' => $body,
            'timeout' => 5,
        ]);

        $statusCode = $response->getStatusCode();
        if ($statusCode >= 300) {
            throw new \RuntimeException("Bitrix service error (HTTP {$statusCode})");
        }
    }

    private function generateUuidV4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}

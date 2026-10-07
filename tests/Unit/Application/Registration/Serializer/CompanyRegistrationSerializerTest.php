<?php

namespace App\Tests\Unit\Application\Registration\Serializer;

use App\Application\Registration\Event\CompanyRegistrationCompletedEvent;
use App\Application\Registration\Serializer\CompanyRegistrationSerializer;
use PHPUnit\Framework\TestCase;

class CompanyRegistrationSerializerTest extends TestCase
{
    private CompanyRegistrationSerializer $serializer;

    protected function setUp(): void
    {
        $this->serializer = new CompanyRegistrationSerializer();
    }

    public function testToArray(): void
    {
        $event = new CompanyRegistrationCompletedEvent(
            eventId: '8f631319-91ea-4fe8-b921-8ea6516e117c',
            eventType: 'company.registration.completed',
            eventVersion: 1,
            occurredAt: '2026-06-24T12:00:00.000Z',
            traceId: '51874597-c6ce-4e25-88fd-f8e863ffceb1',
            correlationId: '51874597-c6ce-4e25-88fd-f8e863ffceb1',
            service: 'mp-core',
            companyId: 42,
            legalEntity: '7712345678',
            companyName: 'ООО Пример',
            legalFormName: 'Общество с ограниченной ответственностью',
            roleCode: 'BUYER',
            countryCode: 'KZ',
            status: 'moderation',
        );

        $result = $this->serializer->toArray($event);

        self::assertSame('8f631319-91ea-4fe8-b921-8ea6516e117c', $result['event_id']);
        self::assertSame('company.registration.completed', $result['event_type']);
        self::assertSame(1, $result['event_version']);
        self::assertSame('2026-06-24T12:00:00.000Z', $result['occurred_at']);
        self::assertSame('51874597-c6ce-4e25-88fd-f8e863ffceb1', $result['trace_id']);
        self::assertSame('51874597-c6ce-4e25-88fd-f8e863ffceb1', $result['correlation_id']);
        self::assertSame(['service' => 'mp-core'], $result['producer']);
        self::assertSame('', $result['callback']);

        self::assertSame(42, $result['payload']['company_id']);
        self::assertSame('7712345678', $result['payload']['legal_entity']);
        self::assertSame('ООО Пример', $result['payload']['company_name']);
        self::assertSame('Общество с ограниченной ответственностью', $result['payload']['legal_form_name']);
        self::assertSame('BUYER', $result['payload']['role_code']);
        self::assertSame('KZ', $result['payload']['country_code']);
        self::assertSame('moderation', $result['payload']['status']);
    }
}

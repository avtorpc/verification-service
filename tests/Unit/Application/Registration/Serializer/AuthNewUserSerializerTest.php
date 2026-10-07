<?php

namespace App\Tests\Unit\Application\Registration\Serializer;

use App\Application\Registration\Event\AuthNewUserEvent;
use App\Application\Registration\Serializer\AuthNewUserSerializer;
use PHPUnit\Framework\TestCase;

class AuthNewUserSerializerTest extends TestCase
{
    private AuthNewUserSerializer $serializer;

    protected function setUp(): void
    {
        $this->serializer = new AuthNewUserSerializer();
    }

    public function testToArray(): void
    {
        $event = new AuthNewUserEvent(
            eventId: '8f631319-91ea-4fe8-b921-8ea6516e117c',
            eventType: 'auth.new.user',
            eventVersion: 1,
            occurredAt: '2026-06-17T08:52:40+00:00',
            traceId: '51874597-c6ce-4e25-88fd-f8e863ffceb1',
            correlationId: '51874597-c6ce-4e25-88fd-f8e863ffceb1',
            service: 'verification-service',
            payload: [
                'user_uuid' => '51874597-c6ce-4e25-88fd-f8e863ffceb1',
                'email' => 'user@example.com',
                'last_name' => 'Иванов',
                'first_name' => 'Иван',
                'patronymic' => 'Иванович',
                'phone_number' => '+79152314454',
                'verification_channel_id' => 'sms',
            ],
        );

        $result = $this->serializer->toArray($event);

        self::assertSame('8f631319-91ea-4fe8-b921-8ea6516e117c', $result['event_id']);
        self::assertSame('auth.new.user', $result['event_type']);
        self::assertSame(1, $result['event_version']);
        self::assertSame('2026-06-17T08:52:40+00:00', $result['occurred_at']);
        self::assertSame('51874597-c6ce-4e25-88fd-f8e863ffceb1', $result['trace_id']);
        self::assertSame('51874597-c6ce-4e25-88fd-f8e863ffceb1', $result['correlation_id']);
        self::assertSame(['service' => 'verification-service'], $result['producer']);
        self::assertSame('', $result['callback']);
        self::assertSame('51874597-c6ce-4e25-88fd-f8e863ffceb1', $result['payload']['user_uuid']);
        self::assertSame('Иванов', $result['payload']['last_name']);
        self::assertSame('Иван', $result['payload']['first_name']);
    }
}

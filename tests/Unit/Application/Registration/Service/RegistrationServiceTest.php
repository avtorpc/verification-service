<?php

namespace App\Tests\Unit\Application\Registration\Service;

use App\Application\Registration\DTO\RegistrationCompleteRequest;
use App\Application\Registration\Serializer\AuthNewUserSerializer;
use App\Application\Registration\Service\RegistrationService;
use App\Application\Shared\EventPublisherInterface;
use PHPUnit\Framework\TestCase;

class RegistrationServiceTest extends TestCase
{
    private EventPublisherInterface $eventPublisher;
    private AuthNewUserSerializer $serializer;
    private RegistrationService $service;

    protected function setUp(): void
    {
        $this->eventPublisher = $this->createMock(EventPublisherInterface::class);
        $this->serializer = new AuthNewUserSerializer();
        $this->service = new RegistrationService($this->eventPublisher, $this->serializer);
    }

    public function testCompleteReturnsRequestId(): void
    {
        $this->eventPublisher
            ->expects(self::once())
            ->method('publish');

        $request = new RegistrationCompleteRequest(
            firstName: 'Иван',
            lastName: 'Петров',
            email: 'ivan@example.com',
            phoneNumber: '+79152314454',
            verificationChannelId: 'sms',
            patronymic: 'Сергеевич',
        );

        $result = $this->service->complete($request);

        self::assertArrayHasKey('requestId', $result);
        self::assertSame('ok', $result['status']);
        self::assertNotEmpty($result['requestId']);
    }

    public function testCompletePublishesKafkaEvent(): void
    {
        $this->eventPublisher
            ->expects(self::once())
            ->method('publish')
            ->with(self::callback(function (array $event) {
                return $event['event_type'] === 'auth.new.user'
                    && $event['event_version'] === 1
                    && $event['producer']['service'] === 'verification-service'
                    && $event['payload']['email'] === 'ivan@example.com'
                    && $event['payload']['phone_number'] === '+79152314454'
                    && $event['payload']['first_name'] === 'Иван'
                    && $event['payload']['last_name'] === 'Петров'
                    && $event['payload']['patronymic'] === 'Сергеевич'
                    && $event['payload']['verification_channel_id'] === 'sms'
                    && isset($event['event_id'])
                    && isset($event['payload']['user_uuid'])
                    && $event['trace_id'] === $event['payload']['user_uuid']
                    && $event['correlation_id'] === $event['payload']['user_uuid'];
            }));

        $request = new RegistrationCompleteRequest(
            firstName: 'Иван',
            lastName: 'Петров',
            email: 'ivan@example.com',
            phoneNumber: '+79152314454',
            verificationChannelId: 'sms',
            patronymic: 'Сергеевич',
        );

        $this->service->complete($request);
    }

    public function testCompleteWithoutPatronymic(): void
    {
        $this->eventPublisher
            ->expects(self::once())
            ->method('publish')
            ->with(self::callback(fn(array $event) => $event['payload']['patronymic'] === ''));

        $request = new RegistrationCompleteRequest(
            firstName: 'Иван',
            lastName: 'Петров',
            email: 'ivan@example.com',
            phoneNumber: '+79152314454',
            verificationChannelId: 'email',
        );

        $result = $this->service->complete($request);

        self::assertArrayHasKey('requestId', $result);
    }
}

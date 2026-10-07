<?php

namespace App\Application\Registration\Service;

use App\Application\Registration\DTO\RegistrationCompleteRequest;
use App\Application\Registration\Event\AuthNewUserEvent;
use App\Application\Registration\Serializer\AuthNewUserSerializer;
use App\Application\Shared\EventPublisherInterface;

class RegistrationService
{
    public function __construct(
        private EventPublisherInterface $eventPublisher,
        private AuthNewUserSerializer $serializer,
        private string $serviceName = 'verification-service',
    ) {
    }

    public function complete(RegistrationCompleteRequest $request): array
    {
        $userUuid = $this->generateUuidV4();
        $now = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d\TH:i:s+00:00');

        $event = new AuthNewUserEvent(
            eventId: $this->generateUuidV4(),
            eventType: 'auth.new.user',
            eventVersion: 1,
            occurredAt: $now,
            traceId: $userUuid,
            correlationId: $userUuid,
            service: $this->serviceName,
            payload: [
                'user_uuid' => $userUuid,
                'email' => $request->email,
                'last_name' => $request->lastName,
                'first_name' => $request->firstName,
                'patronymic' => $request->patronymic ?? '',
                'phone_number' => $request->phoneNumber,
                'verification_channel_id' => $request->verificationChannelId,
            ],
        );

        $this->eventPublisher->publish($this->serializer->toArray($event));

        return [
            'requestId' => $userUuid,
            'status' => 'ok',
        ];
    }

    private function generateUuidV4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}

<?php

namespace App\Domain\Event;

use App\Shared\Time\ClockInterface;
use Ramsey\Uuid\Uuid;

/**
 * Класс для создания запроса в кафку на создание нового пользователя после регистрации
 */
class CreateNewUserEventFactory
{
    public function __construct(
        private readonly ClockInterface $clock
    ) {}

    public function create(
        array $user
    ): CreateNewUserEvent {
        return new CreateNewUserEvent(
            eventId: Uuid::uuid4()->toString(),
            eventType: 'auth.new.user',
            eventVersion: 1,
            occurredAt: $this->clock->nowIso(),

            traceId: $user['request_id'],
            correlationId: $user['request_id'],

            service: 'verification-service',

            userUuid: $user['request_id'],
            email: $user['email'],

            lastName: $user['last_name'],
            firstName: $user['first_name'],
            patronymic: $user['patronymic'] ?? null,

            phoneNumber: $user['phone_number'],
            verificationChannelId: $user['verification_channel_id'],

            callback: '',
        );
    }
}

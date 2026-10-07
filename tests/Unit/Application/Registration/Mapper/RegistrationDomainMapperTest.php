<?php

namespace App\Tests\Unit\Application\Registration\Mapper;

use App\Application\Registration\DTO\RegistrationCompleteRequest;
use App\Application\Registration\Mapper\RegistrationDomainMapper;
use App\Shared\Exception\ValidationException;
use PHPUnit\Framework\TestCase;

class RegistrationDomainMapperTest extends TestCase
{
    private RegistrationDomainMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new RegistrationDomainMapper();
    }

    public function testMapValidData(): void
    {
        $raw = new RegistrationCompleteRequest(
            firstName: 'Иван',
            lastName: 'Петров',
            email: 'ivan@example.com',
            phoneNumber: '+79152314454',
            verificationChannelId: 'sms',
            patronymic: 'Сергеевич',
        );

        $domain = $this->mapper->map($raw);

        self::assertSame('Иван', $domain->firstName);
        self::assertSame('Петров', $domain->lastName);
        self::assertSame('ivan@example.com', $domain->email);
        self::assertSame('+79152314454', $domain->phoneNumber);
        self::assertSame('sms', $domain->verificationChannelId);
        self::assertSame('Сергеевич', $domain->patronymic);
    }

    public function testMapMissingFirstNameThrows(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('firstName is required');

        $raw = new RegistrationCompleteRequest(
            firstName: '',
            lastName: 'Петров',
            email: 'ivan@example.com',
            phoneNumber: '+79152314454',
            verificationChannelId: 'sms',
        );

        $this->mapper->map($raw);
    }

    public function testMapMissingLastNameThrows(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('lastName is required');

        $raw = new RegistrationCompleteRequest(
            firstName: 'Иван',
            lastName: '',
            email: 'ivan@example.com',
            phoneNumber: '+79152314454',
            verificationChannelId: 'sms',
        );

        $this->mapper->map($raw);
    }

    public function testMapMissingEmailThrows(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('email is required');

        $raw = new RegistrationCompleteRequest(
            firstName: 'Иван',
            lastName: 'Петров',
            email: '',
            phoneNumber: '+79152314454',
            verificationChannelId: 'sms',
        );

        $this->mapper->map($raw);
    }

    public function testMapMissingPhoneNumberThrows(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('phoneNumber is required');

        $raw = new RegistrationCompleteRequest(
            firstName: 'Иван',
            lastName: 'Петров',
            email: 'ivan@example.com',
            phoneNumber: '',
            verificationChannelId: 'sms',
        );

        $this->mapper->map($raw);
    }

    public function testMapInvalidVerificationChannelIdThrows(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('verificationChannelId must be one of');

        $raw = new RegistrationCompleteRequest(
            firstName: 'Иван',
            lastName: 'Петров',
            email: 'ivan@example.com',
            phoneNumber: '+79152314454',
            verificationChannelId: 'invalid',
        );

        $this->mapper->map($raw);
    }

    public function testMapAllowsEmailVerificationChannel(): void
    {
        $raw = new RegistrationCompleteRequest(
            firstName: 'Иван',
            lastName: 'Петров',
            email: 'ivan@example.com',
            phoneNumber: '+79152314454',
            verificationChannelId: 'email',
        );

        $domain = $this->mapper->map($raw);

        self::assertSame('email', $domain->verificationChannelId);
    }

    public function testMapAllowsTelegramVerificationChannel(): void
    {
        $raw = new RegistrationCompleteRequest(
            firstName: 'Иван',
            lastName: 'Петров',
            email: 'ivan@example.com',
            phoneNumber: '+79152314454',
            verificationChannelId: 'telegram',
        );

        $domain = $this->mapper->map($raw);

        self::assertSame('telegram', $domain->verificationChannelId);
    }

    public function testMapWithoutPatronymic(): void
    {
        $raw = new RegistrationCompleteRequest(
            firstName: 'Иван',
            lastName: 'Петров',
            email: 'ivan@example.com',
            phoneNumber: '+79152314454',
            verificationChannelId: 'sms',
        );

        $domain = $this->mapper->map($raw);

        self::assertNull($domain->patronymic);
    }
}

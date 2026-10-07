<?php

namespace App\Tests\Unit\Application\Registration\Mapper;

use App\Application\Registration\Mapper\RegistrationJsonMapper;
use PHPUnit\Framework\TestCase;

class RegistrationJsonMapperTest extends TestCase
{
    private RegistrationJsonMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new RegistrationJsonMapper();
    }

    public function testMapFullData(): void
    {
        $data = [
            'firstName' => 'Иван',
            'lastName' => 'Петров',
            'email' => 'ivan@example.com',
            'phoneNumber' => '+79152314454',
            'verificationChannelId' => 'sms',
            'patronymic' => 'Сергеевич',
        ];

        $raw = $this->mapper->map($data);

        self::assertSame('Иван', $raw->firstName);
        self::assertSame('Петров', $raw->lastName);
        self::assertSame('ivan@example.com', $raw->email);
        self::assertSame('+79152314454', $raw->phoneNumber);
        self::assertSame('sms', $raw->verificationChannelId);
        self::assertSame('Сергеевич', $raw->patronymic);
    }

    public function testMapMinimalData(): void
    {
        $data = [
            'firstName' => 'Иван',
            'lastName' => 'Петров',
            'email' => 'ivan@example.com',
            'phoneNumber' => '+79152314454',
        ];

        $raw = $this->mapper->map($data);

        self::assertSame('Иван', $raw->firstName);
        self::assertSame('Петров', $raw->lastName);
        self::assertSame('ivan@example.com', $raw->email);
        self::assertSame('+79152314454', $raw->phoneNumber);
        self::assertSame('sms', $raw->verificationChannelId);
        self::assertNull($raw->patronymic);
    }

    public function testMapCamelCaseKeys(): void
    {
        $data = [
            'firstName' => 'Петр',
            'lastName' => 'Сидоров',
            'email' => 'petr@example.com',
            'phoneNumber' => '+71234567890',
            'verificationChannelId' => 'email',
        ];

        $raw = $this->mapper->map($data);

        self::assertSame('Петр', $raw->firstName);
        self::assertSame('Сидоров', $raw->lastName);
        self::assertSame('email', $raw->verificationChannelId);
    }
}

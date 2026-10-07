<?php

namespace App\Tests\Unit\Shared\Time;

use App\Shared\Time\SystemClock;
use PHPUnit\Framework\TestCase;

class SystemClockTest extends TestCase
{
    public function testNowReturnsDateTimeImmutable(): void
    {
        $clock = new SystemClock();
        $now = $clock->now();

        self::assertInstanceOf(\DateTimeImmutable::class, $now);
    }

    public function testNowInUtc(): void
    {
        $clock = new SystemClock();
        $now = $clock->now();

        self::assertSame('UTC', $now->getTimezone()->getName());
    }

    public function testNowFormattedReturnsIsoWithMicroseconds(): void
    {
        $clock = new SystemClock();
        $formatted = $clock->nowFormatted();

        self::assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}Z$/', $formatted);
    }
}

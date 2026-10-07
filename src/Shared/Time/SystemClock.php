<?php

namespace App\Shared\Time;

final class SystemClock implements ClockInterface
{
    private readonly \DateTimeZone $timezone;

    public function __construct(string $timezone)
    {
        $this->timezone = new \DateTimeZone($timezone);
    }

    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', $this->timezone);
    }

    public function nowFormatted(): string
    {
        return $this->now()->format('Y-m-d\TH:i:s.v\Z');
    }

    public function format(\DateTimeImmutable $dateTime): string
    {
        return $dateTime
            ->setTimezone($this->timezone)
            ->format('Y-m-d H:i:s');
    }

    public function nowIso(): string
    {
        return $this->now()->format('Y-m-d\TH:i:s.v\Z');
    }

    public function parseIso(string $value): \DateTimeImmutable
    {
        try {
            $dt = new \DateTimeImmutable($value);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException("Invalid ISO datetime: {$value}");
        }

        return $dt->setTimezone($this->timezone);
    }

    public function nowTimestamp(): int
    {
        return time();
    }
}

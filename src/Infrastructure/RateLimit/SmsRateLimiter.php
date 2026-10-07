<?php

declare(strict_types=1);

namespace App\Infrastructure\RateLimit;

final class SmsRateLimiter
{
    private const PREFIX = 'sms_cooldown:';

    public function __construct(
        private RedisRateLimiter $limiter
    ) {}

    public function tryAcquire(string $phone, int $ttl): bool
    {
        $key = self::PREFIX . $this->normalize($phone);

        return $this->limiter->acquire($key, $ttl);
    }

    private function normalize(string $phone): string
    {
        $phone = preg_replace('/\D+/', '', $phone);

        return sha1($phone);
    }
}

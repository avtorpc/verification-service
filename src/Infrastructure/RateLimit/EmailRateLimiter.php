<?php

declare(strict_types=1);

namespace App\Infrastructure\RateLimit;

use App\Infrastructure\RateLimit\RedisRateLimiter;

final class EmailRateLimiter
{
    private const PREFIX = 'email_cooldown:';

    public function __construct(
        private RedisRateLimiter $limiter
    ) {}

    public function tryAcquire(string $email, int $ttl): bool
    {
        $key = self::PREFIX . $this->normalize($email);

        return $this->limiter->acquire($key, $ttl);
    }

    private function normalize(string $email): string
    {
        return sha1(mb_strtolower(trim($email)));
    }
}

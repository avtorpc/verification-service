<?php

declare(strict_types=1);

namespace App\Infrastructure\RateLimit;

use Predis\Client;

final class RedisRateLimiter
{
    public function __construct(private Client $redis) {}

    public function acquire(string $key, int $ttl): bool
    {
        $result = $this->redis->executeRaw([
            'SET',
            $key,
            '1',
            'NX',
            'EX',
            $ttl,
        ]);

        return $result === 'OK';
    }
}

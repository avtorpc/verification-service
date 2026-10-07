<?php

namespace App\Shared;

final readonly class ApplicationInfo
{
    public function __construct(
        private string $name,
        private string $version,
    ) {
    }

    public function toArray(): array
    {
        return [
            'service' => $this->name,
            'version' => $this->version,
            'status' => 'ok',
            'timestamp' => (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d\TH:i:s\Z'),
        ];
    }
}

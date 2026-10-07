<?php

namespace App\Tests\Unit\Shared;

use App\Shared\ApplicationInfo;
use PHPUnit\Framework\TestCase;

class ApplicationInfoTest extends TestCase
{
    public function testToArrayReturnsServiceInfo(): void
    {
        $info = new ApplicationInfo('mp-core', '1.0.0');
        $data = $info->toArray();

        self::assertSame('mp-core', $data['service']);
        self::assertSame('1.0.0', $data['version']);
        self::assertSame('ok', $data['status']);
        self::assertArrayHasKey('timestamp', $data);
    }

    public function testToArrayUsesUtcTimestamp(): void
    {
        $info = new ApplicationInfo('test', '2.0.0');
        $data = $info->toArray();

        self::assertStringEndsWith('Z', $data['timestamp']);
    }
}

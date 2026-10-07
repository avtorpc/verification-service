<?php

namespace App\Tests\Unit\Application\Dictionaries;

use App\Application\Dictionaries\DictionaryNotFoundException;
use App\Application\Dictionaries\DictionaryService;
use App\Domain\Dictionaries\DictionaryProviderInterface;
use PHPUnit\Framework\TestCase;

class DictionaryServiceTest extends TestCase
{
    public function testListReturnsProviderNames(): void
    {
        $provider = $this->createMock(DictionaryProviderInterface::class);
        $provider->method('getName')->willReturn('countries');

        $service = new DictionaryService([$provider]);

        self::assertSame(['countries'], $service->list());
    }

    public function testGetReturnsItems(): void
    {
        $provider = $this->createMock(DictionaryProviderInterface::class);
        $provider->method('getName')->willReturn('countries');
        $provider->method('getItems')->willReturn(['RU' => 'Россия']);

        $service = new DictionaryService([$provider]);

        self::assertSame(['RU' => 'Россия'], $service->get('countries'));
    }

    public function testGetWithActiveFilter(): void
    {
        $provider = $this->createMock(DictionaryProviderInterface::class);
        $provider->method('getName')->willReturn('countries');
        $provider->expects(self::once())
            ->method('getItems')
            ->with(true)
            ->willReturn(['RU' => 'Россия']);

        $service = new DictionaryService([$provider]);

        self::assertSame(['RU' => 'Россия'], $service->get('countries', true));
    }

    public function testGetUnknownDictionaryThrows(): void
    {
        $this->expectException(DictionaryNotFoundException::class);

        $service = new DictionaryService([]);
        $service->get('unknown');
    }

    public function testMultipleProviders(): void
    {
        $countries = $this->createMock(DictionaryProviderInterface::class);
        $countries->method('getName')->willReturn('countries');
        $countries->method('getItems')->willReturn(['RU' => 'Россия']);

        $roles = $this->createMock(DictionaryProviderInterface::class);
        $roles->method('getName')->willReturn('roles');
        $roles->method('getItems')->willReturn(['SELLER' => 'Продавец']);

        $service = new DictionaryService([$countries, $roles]);

        self::assertSame(['countries', 'roles'], $service->list());
        self::assertSame(['SELLER' => 'Продавец'], $service->get('roles'));
    }

    public function testEmptyProviders(): void
    {
        $service = new DictionaryService([]);
        self::assertSame([], $service->list());
    }
}

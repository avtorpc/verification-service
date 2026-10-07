<?php

namespace App\Tests\Unit\Controller\Api\Dictionaries;

use App\Application\Dictionaries\DictionaryService;
use App\Controller\Api\Dictionaries\DictionariesController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class DictionariesControllerTest extends TestCase
{
    private DictionaryService $service;
    private DictionariesController $controller;

    protected function setUp(): void
    {
        $this->service = $this->createMock(DictionaryService::class);
        $this->controller = new DictionariesController($this->service);
    }

    public function testListReturnsDictionaries(): void
    {
        $this->service
            ->expects(self::once())
            ->method('list')
            ->willReturn(['countries', 'roles']);

        $response = $this->controller->list();
        $body = json_decode($response->getContent(), true);

        self::assertTrue($body['success']);
        self::assertArrayHasKey('timestamp', $body);
        self::assertSame(2, $body['data']['total']);
        self::assertSame(['countries', 'roles'], $body['data']['items']);
    }

    public function testListEmpty(): void
    {
        $this->service
            ->expects(self::once())
            ->method('list')
            ->willReturn([]);

        $response = $this->controller->list();
        $body = json_decode($response->getContent(), true);

        self::assertTrue($body['success']);
        self::assertSame(0, $body['data']['total']);
        self::assertSame([], $body['data']['items']);
    }

    public function testGetReturnsItems(): void
    {
        $this->service
            ->expects(self::once())
            ->method('get')
            ->with('countries', true)
            ->willReturn([]);

        $request = new Request();

        $response = $this->controller->get('countries', $request);
        $body = json_decode($response->getContent(), true);

        self::assertTrue($body['success']);
        self::assertSame(0, $body['data']['total']);
    }

    public function testGetWithOnlyActiveFalse(): void
    {
        $this->service
            ->expects(self::once())
            ->method('get')
            ->with('countries', false)
            ->willReturn([]);

        $request = new Request(['only_active' => '0']);

        $response = $this->controller->get('countries', $request);
        $body = json_decode($response->getContent(), true);

        self::assertTrue($body['success']);
    }
}

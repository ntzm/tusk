<?php

namespace TuskTests;

use PHPUnit\Framework\TestCase;
use Slim\Psr7\Headers;
use Slim\Psr7\Request;
use Slim\Psr7\Response;
use Slim\Psr7\Stream;
use Slim\Psr7\Uri;
use Tusk\Storage\Storage;
use Tusk\Tus;

final class TusTest extends TestCase
{
    public function testMethodNotAllowed(): void
    {
        $tus = new Tus($this->createMock(Storage::class));

        $request = new Request(
            'SOMETHING',
            new Uri('', ''),
            new Headers([]),
            [],
            [],
            new Stream(stream_context_create([]))
        );

        $response = $tus->handle($request, new Response());

        $this->assertSame(405, $response->getStatusCode());
    }

    public function testNegativeMaxFileSize(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Tus($this->createMock(Storage::class), -1);
    }
}

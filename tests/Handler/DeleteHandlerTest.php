<?php

namespace TuskTests\Handler;

use PHPUnit\Framework\TestCase;
use Slim\Psr7\Headers;
use Slim\Psr7\Request;
use Slim\Psr7\Response;
use Slim\Psr7\Stream;
use Slim\Psr7\Uri;
use Tusk\FileNotFound;
use Tusk\Handler\DeleteHandler;
use Tusk\Storage\Storage;

final class DeleteHandlerTest extends TestCase
{
    public function testDeletes(): void
    {
        $storage = $this->createMock(Storage::class);
        $storage->expects($this->once())->method('delete')->with('foo');

        $handler = new DeleteHandler($storage);

        $request = new Request(
            'DELETE',
            new Uri('', ''),
            new Headers(['Tus-Resumable' => '1.0.0']),
            [],
            [],
            new Stream(stream_context_create([]))
        );
        $request = $request->withAttribute('id', 'foo');

        $response = $handler->__invoke($request, new Response());

        $this->assertSame(['Tus-Resumable' => ['1.0.0']], $response->getHeaders());
        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame('', $response->getBody()->getContents());
    }

    public function test404IfNoId(): void
    {
        $storage = $this->createMock(Storage::class);
        $storage->expects($this->never())->method('delete');

        $handler = new DeleteHandler($storage);

        $request = new Request(
            'DELETE',
            new Uri('', ''),
            new Headers(['Tus-Resumable' => '1.0.0']),
            [],
            [],
            new Stream(stream_context_create([]))
        );

        $response = $handler->__invoke($request, new Response());

        $this->assertSame(['Tus-Resumable' => ['1.0.0']], $response->getHeaders());
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('', $response->getBody()->getContents());
    }

    public function test404IfNotExists(): void
    {
        $storage = $this->createMock(Storage::class);
        $storage->expects($this->once())->method('delete')->willThrowException(new FileNotFound());

        $handler = new DeleteHandler($storage);

        $request = new Request(
            'DELETE',
            new Uri('', ''),
            new Headers(['Tus-Resumable' => '1.0.0']),
            [],
            [],
            new Stream(stream_context_create([]))
        );
        $request = $request->withAttribute('id', 'foo');

        $response = $handler->__invoke($request, new Response());

        $this->assertSame(['Tus-Resumable' => ['1.0.0']], $response->getHeaders());
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('', $response->getBody()->getContents());
    }

    public function testIncorrectVersion(): void
    {
        $handler = new DeleteHandler($this->createMock(Storage::class));

        $request = new Request(
            'DELETE',
            new Uri('', ''),
            new Headers(['Tus-Resumable' => '1.0.1']),
            [],
            [],
            new Stream(stream_context_create([]))
        );

        $response = $handler->__invoke($request, new Response());

        $this->assertSame(412, $response->getStatusCode());
        $this->assertSame([
            'Tus-Resumable' => ['1.0.0'],
            'Tus-Version' => ['1.0.0'],
        ], $response->getHeaders());
        $this->assertSame('', $response->getBody()->getContents());
    }
}

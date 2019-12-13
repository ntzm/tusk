<?php

namespace TuskTests\Handler;

use PHPUnit\Framework\TestCase;
use Slim\Psr7\Headers;
use Slim\Psr7\Request;
use Slim\Psr7\Response;
use Slim\Psr7\Stream;
use Slim\Psr7\Uri;
use Tusk\FileNotFound;
use Tusk\Handler\HeadHandler;
use Tusk\Storage\Storage;

final class HeadHandlerTest extends TestCase
{
    public function testHeadNoMetadata(): void
    {
        $storage = $this->createMock(Storage::class);
        $storage->expects($this->once())->method('getOffset')->with('foo')->willReturn(1);
        $storage->expects($this->once())->method('getLength')->with('foo')->willReturn(2);
        $storage->expects($this->once())->method('getMetadata')->with('foo')->willReturn(null);

        $handler = new HeadHandler($storage);

        $request = new Request(
            'HEAD',
            new Uri('', ''),
            new Headers(['Tus-Resumable' => '1.0.0']),
            [],
            [],
            new Stream(stream_context_create([]))
        );
        $request = $request->withAttribute('id', 'foo');

        $response = $handler->handle($request, new Response());

        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame([
            'Tus-Resumable' => ['1.0.0'],
            'Cache-Control' => ['no-store'],
            'Upload-Offset' => ['1'],
            'Upload-Length' => ['2'],
        ], $response->getHeaders());
        $this->assertSame('', $response->getBody()->getContents());
    }

    public function testHeadWithMetadata(): void
    {
        $storage = $this->createMock(Storage::class);
        $storage->expects($this->once())->method('getOffset')->with('foo')->willReturn(1);
        $storage->expects($this->once())->method('getLength')->with('foo')->willReturn(2);
        $storage->expects($this->once())->method('getMetadata')->with('foo')->willReturn('bar');

        $handler = new HeadHandler($storage);

        $request = new Request(
            'HEAD',
            new Uri('', ''),
            new Headers(['Tus-Resumable' => '1.0.0']),
            [],
            [],
            new Stream(stream_context_create([]))
        );
        $request = $request->withAttribute('id', 'foo');

        $response = $handler->handle($request, new Response());

        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame([
            'Tus-Resumable' => ['1.0.0'],
            'Cache-Control' => ['no-store'],
            'Upload-Metadata' => ['bar'],
            'Upload-Offset' => ['1'],
            'Upload-Length' => ['2'],
        ], $response->getHeaders());
        $this->assertSame('', $response->getBody()->getContents());
    }

    public function testHeadNoId(): void
    {
        $storage = $this->createMock(Storage::class);

        $handler = new HeadHandler($storage);

        $request = new Request(
            'HEAD',
            new Uri('', ''),
            new Headers(['Tus-Resumable' => '1.0.0']),
            [],
            [],
            new Stream(stream_context_create([]))
        );

        $response = $handler->handle($request, new Response());

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame([
            'Tus-Resumable' => ['1.0.0'],
            'Cache-Control' => ['no-store'],
        ], $response->getHeaders());
        $this->assertSame('', $response->getBody()->getContents());
    }

    public function testHeadGetOffsetNotFound(): void
    {
        $storage = $this->createMock(Storage::class);
        $storage->method('getOffset')->willThrowException(new FileNotFound());

        $handler = new HeadHandler($storage);

        $request = new Request(
            'HEAD',
            new Uri('', ''),
            new Headers(['Tus-Resumable' => '1.0.0']),
            [],
            [],
            new Stream(stream_context_create([]))
        );
        $request = $request->withAttribute('id', 'foo');

        $response = $handler->handle($request, new Response());

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame([
            'Tus-Resumable' => ['1.0.0'],
            'Cache-Control' => ['no-store'],
        ], $response->getHeaders());
        $this->assertSame('', $response->getBody()->getContents());
    }

    public function testHeadGetLengthNotFound(): void
    {
        $storage = $this->createMock(Storage::class);
        $storage->method('getLength')->willThrowException(new FileNotFound());

        $handler = new HeadHandler($storage);

        $request = new Request(
            'HEAD',
            new Uri('', ''),
            new Headers(['Tus-Resumable' => '1.0.0']),
            [],
            [],
            new Stream(stream_context_create([]))
        );
        $request = $request->withAttribute('id', 'foo');

        $response = $handler->handle($request, new Response());

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame([
            'Tus-Resumable' => ['1.0.0'],
            'Cache-Control' => ['no-store'],
        ], $response->getHeaders());
        $this->assertSame('', $response->getBody()->getContents());
    }

    public function testHeadGetMetadataNotFound(): void
    {
        $storage = $this->createMock(Storage::class);
        $storage->method('getMetadata')->willThrowException(new FileNotFound());

        $handler = new HeadHandler($storage);

        $request = new Request(
            'HEAD',
            new Uri('', ''),
            new Headers(['Tus-Resumable' => '1.0.0']),
            [],
            [],
            new Stream(stream_context_create([]))
        );
        $request = $request->withAttribute('id', 'foo');

        $response = $handler->handle($request, new Response());

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame([
            'Tus-Resumable' => ['1.0.0'],
            'Cache-Control' => ['no-store'],
        ], $response->getHeaders());
        $this->assertSame('', $response->getBody()->getContents());
    }

    public function testIncorrectVersion(): void
    {
        $handler = new HeadHandler($this->createMock(Storage::class));

        $request = new Request(
            'HEAD',
            new Uri('', ''),
            new Headers(['Tus-Resumable' => '1.0.1']),
            [],
            [],
            new Stream(stream_context_create([]))
        );

        $response = $handler->handle($request, new Response());

        $this->assertSame(412, $response->getStatusCode());
        $this->assertSame([
            'Tus-Resumable' => ['1.0.0'],
            'Tus-Version' => ['1.0.0'],
        ], $response->getHeaders());
        $this->assertSame('', $response->getBody()->getContents());
    }
}

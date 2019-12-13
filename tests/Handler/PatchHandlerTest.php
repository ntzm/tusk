<?php

namespace TuskTests\Handler;

use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Slim\Psr7\Headers;
use Slim\Psr7\Request;
use Slim\Psr7\Response;
use Slim\Psr7\Stream;
use Slim\Psr7\Uri;
use Tusk\FileNotFound;
use Tusk\Handler\PatchHandler;
use Tusk\ShouldNotHappen;
use Tusk\Storage\Storage;

final class PatchHandlerTest extends TestCase
{
    public function testPatch(): void
    {
        $storage = $this->createMock(Storage::class);
        $storage->expects($this->exactly(2))->method('getOffset')->with('foo')->willReturnOnConsecutiveCalls(5, 9);
        $storage->expects($this->once())->method('append')->with('foo', $this->callback(function ($data): bool {
            $this->assertIsResource($data);
            $this->assertSame('abcd', stream_get_contents($data));

            return true;
        }));

        $handler = new PatchHandler($storage, $this->createMock(EventDispatcherInterface::class));

        $stream = fopen('data://text/plain,abcd', 'r');
        $this->assertIsResource($stream);

        $request = new Request(
            'PATCH',
            new Uri('', ''),
            new Headers([
                'Tus-Resumable' => '1.0.0',
                'Content-Type' => 'application/offset+octet-stream',
                'Upload-Offset' => '5',
            ]),
            [],
            [],
            new Stream($stream)
        );
        $request = $request->withAttribute('id', 'foo');

        $response = $handler->handle($request, new Response());

        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame([
            'Tus-Resumable' => ['1.0.0'],
            'Upload-Offset' => ['9'],
        ], $response->getHeaders());
        $this->assertSame('', $response->getBody()->getContents());
    }

    public function testPatchWithIncorrectContentType(): void
    {
        $handler = new PatchHandler($this->createMock(Storage::class), $this->createMock(EventDispatcherInterface::class));

        $request = new Request(
            'PATCH',
            new Uri('', ''),
            new Headers([
                'Tus-Resumable' => '1.0.0',
                'Content-Type' => 'text/plain',
                'Upload-Offset' => '5',
            ]),
            [],
            [],
            new Stream(stream_context_create([]))
        );
        $request = $request->withAttribute('id', 'foo');

        $response = $handler->handle($request, new Response());

        $this->assertSame(415, $response->getStatusCode());
        $this->assertSame([
            'Tus-Resumable' => ['1.0.0'],
        ], $response->getHeaders());
        $this->assertSame('', $response->getBody()->getContents());
    }

    public function testPatchNoId(): void
    {
        $handler = new PatchHandler($this->createMock(Storage::class), $this->createMock(EventDispatcherInterface::class));

        $request = new Request(
            'PATCH',
            new Uri('', ''),
            new Headers([
                'Tus-Resumable' => '1.0.0',
                'Content-Type' => 'application/offset+octet-stream',
                'Upload-Offset' => '5',
            ]),
            [],
            [],
            new Stream(stream_context_create([]))
        );

        $response = $handler->handle($request, new Response());

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame([
            'Tus-Resumable' => ['1.0.0'],
        ], $response->getHeaders());
        $this->assertSame('', $response->getBody()->getContents());
    }

    public function testPatchWithConflictingOffsets(): void
    {
        $storage = $this->createMock(Storage::class);
        $storage->expects($this->once())->method('getOffset')->with('foo')->willReturn(5);
        $storage->expects($this->never())->method('append');

        $handler = new PatchHandler($storage, $this->createMock(EventDispatcherInterface::class));

        $stream = fopen('data://text/plain,abcd', 'r');
        $this->assertIsResource($stream);

        $request = new Request(
            'PATCH',
            new Uri('', ''),
            new Headers([
                'Tus-Resumable' => '1.0.0',
                'Content-Type' => 'application/offset+octet-stream',
                'Upload-Offset' => '4',
            ]),
            [],
            [],
            new Stream($stream)
        );
        $request = $request->withAttribute('id', 'foo');

        $response = $handler->handle($request, new Response());

        $this->assertSame(409, $response->getStatusCode());
        $this->assertSame([
            'Tus-Resumable' => ['1.0.0'],
        ], $response->getHeaders());
        $this->assertSame('', $response->getBody()->getContents());
    }

    public function testPatchFileNotFound(): void
    {
        $storage = $this->createMock(Storage::class);
        $storage->expects($this->once())->method('getOffset')->with('foo')->willThrowException(new FileNotFound());

        $handler = new PatchHandler($storage, $this->createMock(EventDispatcherInterface::class));

        $stream = fopen('data://text/plain,abcd', 'r');
        $this->assertIsResource($stream);

        $request = new Request(
            'PATCH',
            new Uri('', ''),
            new Headers([
                'Tus-Resumable' => '1.0.0',
                'Content-Type' => 'application/offset+octet-stream',
                'Upload-Offset' => '5',
            ]),
            [],
            [],
            new Stream($stream)
        );
        $request = $request->withAttribute('id', 'foo');

        $response = $handler->handle($request, new Response());

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame([
            'Tus-Resumable' => ['1.0.0'],
        ], $response->getHeaders());
        $this->assertSame('', $response->getBody()->getContents());
    }

    public function testExceptionIfResultingOffsetIsNotCorrect(): void
    {
        $storage = $this->createMock(Storage::class);
        $storage->expects($this->exactly(2))->method('getOffset')->with('foo')->willReturnOnConsecutiveCalls(5, 10);
        $storage->expects($this->once())->method('append')->with('foo', $this->callback(function ($data): bool {
            $this->assertIsResource($data);
            $this->assertSame('abcd', stream_get_contents($data));

            return true;
        }));

        $handler = new PatchHandler($storage, $this->createMock(EventDispatcherInterface::class));

        $stream = fopen('data://text/plain,abcd', 'r');
        $this->assertIsResource($stream);

        $request = new Request(
            'PATCH',
            new Uri('', ''),
            new Headers([
                'Tus-Resumable' => '1.0.0',
                'Content-Type' => 'application/offset+octet-stream',
                'Upload-Offset' => '5',
            ]),
            [],
            [],
            new Stream($stream)
        );
        $request = $request->withAttribute('id', 'foo');

        $this->expectException(ShouldNotHappen::class);

        $handler->handle($request, new Response());
    }

    public function testIncorrectVersion(): void
    {
        $handler = new PatchHandler($this->createMock(Storage::class), $this->createMock(EventDispatcherInterface::class));

        $request = new Request(
            'PATCH',
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

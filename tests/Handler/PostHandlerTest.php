<?php

namespace TuskTests\Handler;

use PHPUnit\Framework\TestCase;
use Slim\Psr7\Headers;
use Slim\Psr7\Request;
use Slim\Psr7\Response;
use Slim\Psr7\Stream;
use Slim\Psr7\Uri;
use Tusk\Handler\PostHandler;
use Tusk\IdGenerator\IdGenerator;
use Tusk\LocationGenerator\LocationGenerator;
use Tusk\Storage\Storage;

final class PostHandlerTest extends TestCase
{
    public function testUploadWithoutMaxSizeAndWithoutExpirationAndWithoutMetadata(): void
    {
        $request = new Request(
            'POST',
            new Uri('', ''),
            new Headers([
                'Upload-Length' => '1000',
                'Tus-Resumable' => '1.0.0',
            ]),
            [],
            [],
            new Stream(stream_context_create([]))
        );

        $idGenerator = $this->createMock(IdGenerator::class);
        $idGenerator->expects($this->once())->method('generate')->willReturn('foo');

        $storage = $this->createMock(Storage::class);
        $storage->expects($this->once())->method('create')->with('foo', 1000, null);

        $locationGenerator = $this->createMock(LocationGenerator::class);
        $locationGenerator->expects($this->once())->method('generate')->with($request, 'foo')->willReturn('/files/foo');

        $handler = new PostHandler($storage, $idGenerator, null, $locationGenerator);

        $response = $handler->handle($request, new Response());

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame([
            'Tus-Resumable' => ['1.0.0'],
            'Location' => ['/files/foo'],
        ], $response->getHeaders());
        $this->assertSame('', $response->getBody()->getContents());
    }

    public function testUploadWithMetadata(): void
    {
        $request = new Request(
            'POST',
            new Uri('', ''),
            new Headers([
                'Upload-Length' => '1000',
                'Upload-Metadata' => 'foo YmFy',
                'Tus-Resumable' => '1.0.0',
            ]),
            [],
            [],
            new Stream(stream_context_create([]))
        );

        $idGenerator = $this->createMock(IdGenerator::class);
        $idGenerator->expects($this->once())->method('generate')->willReturn('foo');

        $storage = $this->createMock(Storage::class);
        $storage->expects($this->once())->method('create')->with('foo', 1000, 'foo YmFy');

        $locationGenerator = $this->createMock(LocationGenerator::class);
        $locationGenerator->expects($this->once())->method('generate')->with($request, 'foo')->willReturn('/files/foo');

        $handler = new PostHandler($storage, $idGenerator, null, $locationGenerator);

        $response = $handler->handle($request, new Response());

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame([
            'Tus-Resumable' => ['1.0.0'],
            'Location' => ['/files/foo'],
        ], $response->getHeaders());
        $this->assertSame('', $response->getBody()->getContents());
    }

    public function testUploadWithMaxSizeSameAsUploadSize(): void
    {
        $request = new Request(
            'POST',
            new Uri('', ''),
            new Headers([
                'Upload-Length' => '100',
                'Tus-Resumable' => '1.0.0',
            ]),
            [],
            [],
            new Stream(stream_context_create([]))
        );

        $idGenerator = $this->createMock(IdGenerator::class);
        $idGenerator->expects($this->once())->method('generate')->willReturn('foo');

        $storage = $this->createMock(Storage::class);
        $storage->expects($this->once())->method('create')->with('foo', 100, null);

        $locationGenerator = $this->createMock(LocationGenerator::class);
        $locationGenerator->expects($this->once())->method('generate')->with($request, 'foo')->willReturn('/files/foo');

        $handler = new PostHandler($storage, $idGenerator, 100, $locationGenerator);

        $response = $handler->handle($request, new Response());

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame([
            'Tus-Resumable' => ['1.0.0'],
            'Location' => ['/files/foo'],
        ], $response->getHeaders());
        $this->assertSame('', $response->getBody()->getContents());
    }

    public function testUploadWithMaxSizeSmallerThanUploadSize(): void
    {
        $handler = new PostHandler(
            $this->createMock(Storage::class),
            $this->createMock(IdGenerator::class),
            999,
            $this->createMock(LocationGenerator::class)
        );

        $request = new Request(
            'POST',
            new Uri('', ''),
            new Headers([
                'Upload-Length' => '1000',
                'Tus-Resumable' => '1.0.0',
            ]),
            [],
            [],
            new Stream(stream_context_create([]))
        );

        $response = $handler->handle($request, new Response());

        $this->assertSame(413, $response->getStatusCode());
        $this->assertSame([
            'Tus-Resumable' => ['1.0.0'],
        ], $response->getHeaders());
        $this->assertSame('', $response->getBody()->getContents());
    }

    public function testIncorrectVersion(): void
    {
        $handler = new PostHandler(
            $this->createMock(Storage::class),
            $this->createMock(IdGenerator::class),
            null,
            $this->createMock(LocationGenerator::class)
        );

        $request = new Request(
            'POST',
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

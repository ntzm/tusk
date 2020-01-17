<?php

namespace TuskTests\Handler;

use PHPUnit\Framework\TestCase;
use Slim\Psr7\Headers;
use Slim\Psr7\Request;
use Slim\Psr7\Response;
use Slim\Psr7\Stream;
use Slim\Psr7\Uri;
use Tusk\Handler\OptionsHandler;

final class OptionsHandlerTest extends TestCase
{
    public function testOptionsWithoutMaxFileSize(): void
    {
        $handler = new OptionsHandler(null);

        $request = new Request(
            'OPTIONS',
            new Uri('', ''),
            new Headers(['Tus-Resumable' => '1.0.0']),
            [],
            [],
            new Stream(stream_context_create([]))
        );
        $response = $handler->__invoke($request, new Response());

        $this->assertSame([
            'Tus-Resumable' => ['1.0.0'],
            'Tus-Version' => ['1.0.0'],
            'Tus-Extension' => ['creation,termination'],
        ], $response->getHeaders());

        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame('', $response->getBody()->getContents());
    }

    public function testOptionsWithMaxFileSize(): void
    {
        $handler = new OptionsHandler(100);

        $request = new Request(
            'OPTIONS',
            new Uri('', ''),
            new Headers(['Tus-Resumable' => '1.0.0']),
            [],
            [],
            new Stream(stream_context_create([]))
        );
        $response = $handler->__invoke($request, new Response());

        $this->assertSame([
            'Tus-Resumable' => ['1.0.0'],
            'Tus-Max-Size' => ['100'],
            'Tus-Version' => ['1.0.0'],
            'Tus-Extension' => ['creation,termination'],
        ], $response->getHeaders());

        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame('', $response->getBody()->getContents());
    }
}

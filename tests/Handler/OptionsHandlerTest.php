<?php

namespace TuskTests\Handler;

use PHPUnit\Framework\TestCase;
use Slim\Psr7\Response;
use Tusk\Handler\OptionsHandler;

final class OptionsHandlerTest extends TestCase
{
    public function testOptionsWithoutMaxFileSize(): void
    {
        $handler = new OptionsHandler(null);

        $response = $handler->handle(new Response());

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

        $response = $handler->handle(new Response());

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

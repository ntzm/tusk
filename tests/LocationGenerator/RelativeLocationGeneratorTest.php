<?php

namespace TuskTests\LocationGenerator;

use Generator;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\UriFactory;
use Slim\Psr7\Headers;
use Slim\Psr7\Request;
use Slim\Psr7\Stream;
use Tusk\LocationGenerator\RelativeLocationGenerator;

final class RelativeLocationGeneratorTest extends TestCase
{
    /** @dataProvider provideGenerateCases */
    public function testGenerate(string $expected, string $url, string $id): void
    {
        $generator = new RelativeLocationGenerator();

        $request = new Request(
            'GET',
            (new UriFactory())->createUri($url),
            new Headers([]),
            [],
            [],
            new Stream(stream_context_create([]))
        );

        $this->assertSame($expected, $generator->generate($request, $id));
    }

    /** @return Generator<string[]> */
    public function provideGenerateCases(): Generator
    {
        yield ['/files/bar', 'http://foo.com/files', 'bar'];
        yield ['/files/baz', 'http://foo.com/files/', 'baz'];
        yield ['/files/qux', 'http://foo.com/files///', 'qux'];
    }
}

<?php

namespace TuskTests\Storage;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamFile;
use PHPUnit\Framework\TestCase;
use Tusk\FileNotFound;
use Tusk\Storage\FilesystemStorage;

final class FilesystemStorageTest extends TestCase
{
    public function testGetOffset(): void
    {
        $root = vfsStream::setup('root', null, ['a' => 'abcdef']);

        $storage = new FilesystemStorage($root->url());

        $this->assertSame(6, $storage->getOffset('a'));
    }

    public function testGetOffsetNotFound(): void
    {
        $root = vfsStream::setup('root');

        $storage = new FilesystemStorage($root->url());

        $this->expectException(FileNotFound::class);

        $storage->getOffset('a');
    }

    public function testAppend(): void
    {
        $root = vfsStream::setup('root', null, ['a' => 'abcdef']);

        $storage = new FilesystemStorage($root->url());

        $data = fopen('data:text/plain,ghijk', 'r');
        $this->assertIsResource($data);

        $storage->append('a', $data);

        $this->assertSame('abcdefghijk', file_get_contents($root->url() . '/a'));
        $this->assertSame(11, filesize($root->url() . '/a'));
    }

    public function testAppendNotFound(): void
    {
        $root = vfsStream::setup('root');

        $storage = new FilesystemStorage($root->url());

        $data = fopen('data:text/plain,ghijk', 'r');
        $this->assertIsResource($data);

        $this->expectException(FileNotFound::class);

        $storage->append('a', $data);
    }

    public function testAppendNotReadable(): void
    {
        $root = vfsStream::setup('root');
        $file = new vfsStreamFile('a', 0000);
        $file->setContent('abcdef');
        $root->addChild($file);

        $storage = new FilesystemStorage($root->url());

        $data = fopen('data:text/plain,ghijk', 'r');
        $this->assertIsResource($data);

        $this->expectException(FileNotFound::class);

        $storage->append('a', $data);
    }

    public function testCreate(): void
    {
        $root = vfsStream::setup('root');

        $storage = new FilesystemStorage($root->url());

        $storage->create('a', 50, 'm');

        $this->assertFileExists($root->url() . '/a');
        $this->assertSame(
            ['length' => 50, 'metadata' => 'm'],
            json_decode((string) file_get_contents($root->url() . '/a_meta.json'), true)
        );
    }

    public function testDelete(): void
    {
        $root = vfsStream::setup('root', null, ['a' => 'b', 'a_meta.json' => 'b']);

        $storage = new FilesystemStorage($root->url());

        $storage->delete('a');

        $this->assertFileNotExists($root->url() . '/a');
        $this->assertFileNotExists($root->url() . '/a_meta.json');
    }

    public function testDeleteNotFound(): void
    {
        $root = vfsStream::setup('root');

        $storage = new FilesystemStorage($root->url());

        $this->expectException(FileNotFound::class);

        $storage->delete('a');
    }

    public function testGetLength(): void
    {
        $root = vfsStream::setup('root', null, [
            'a' => 'b',
            'a_meta.json' => json_encode(['length' => 5]),
        ]);

        $storage = new FilesystemStorage($root->url());

        $this->assertSame(5, $storage->getLength('a'));
    }

    public function testGetLengthNotFound(): void
    {
        $root = vfsStream::setup('root');

        $storage = new FilesystemStorage($root->url());

        $this->expectException(FileNotFound::class);

        $storage->getLength('a');
    }

    public function testGetMetadata(): void
    {
        $root = vfsStream::setup('root', null, [
            'a' => 'b',
            'a_meta.json' => json_encode(['metadata' => 'c']),
        ]);

        $storage = new FilesystemStorage($root->url());

        $this->assertSame('c', $storage->getMetaData('a'));
    }

    public function testGetMetadataNotFound(): void
    {
        $root = vfsStream::setup('root');

        $storage = new FilesystemStorage($root->url());

        $this->expectException(FileNotFound::class);

        $storage->getMetaData('a');
    }
}

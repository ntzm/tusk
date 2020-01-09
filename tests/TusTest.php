<?php

namespace TuskTests;

use PHPUnit\Framework\TestCase;
use Tusk\Storage\Storage;
use Tusk\Tus;

final class TusTest extends TestCase
{
    public function testNegativeMaxFileSize(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Tus($this->createMock(Storage::class), -1);
    }
}

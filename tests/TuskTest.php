<?php

namespace TuskTests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Tusk\Storage\Storage;
use Tusk\Tusk;

final class TuskTest extends TestCase
{
    public function testNegativeMaxFileSize(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Tusk($this->createMock(Storage::class), -1);
    }
}

<?php

namespace TuskTests\IdGenerator;

use PHPUnit\Framework\TestCase;
use Tusk\IdGenerator\RandomHexIdGenerator;

final class RandomHexIdGeneratorTest extends TestCase
{
    public function testGeneratesUnique(): void
    {
        $generator = new RandomHexIdGenerator();

        $a = $generator->generate();
        $b = $generator->generate();

        $this->assertSame(32, strlen($a));
        $this->assertSame(32, strlen($b));
        $this->assertNotSame($a, $b);
    }
}

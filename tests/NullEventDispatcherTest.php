<?php

namespace TuskTests;

use PHPUnit\Framework\TestCase;
use Tusk\NullEventDispatcher;

final class NullEventDispatcherTest extends TestCase
{
    public function test(): void
    {
        $dispatcher = new NullEventDispatcher();

        $event = new class() {
        };

        $this->assertSame($event, $dispatcher->dispatch($event));
    }
}

<?php

namespace Tusk\IdGenerator;

final class RandomHexIdGenerator implements IdGenerator
{
    public function generate(): string
    {
        return bin2hex(random_bytes(16));
    }
}

<?php

namespace Tusk;

use Exception;

final class FileNotFound extends Exception
{
    public static function withId(string $id): self
    {
        return new self("File with ID {$id} was not found");
    }
}

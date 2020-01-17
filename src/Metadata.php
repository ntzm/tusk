<?php

namespace Tusk;

use ArrayIterator;
use IteratorAggregate;

final class Metadata implements IteratorAggregate
{
    /** @var array<string, string|true> */
    private $values;

    private function __construct(array $values)
    {
        $this->values = $values;
    }

    public static function fromString(string $metadata): self
    {
        $pairs = explode(',', $metadata);
        $values = [];

        foreach ($pairs as $pair) {
            $parts = explode(' ', $pair);
            $key = $parts[0];

            if (isset($parts[1])) {
                $values[$key] = base64_decode($parts[1]);
            } else {
                $values[$key] = true;
            }
        }

        return new self($values);
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->values);
    }

    /** @return string|true */
    public function get(string $key)
    {
        // todo: exist check

        return $this->values[$key];
    }

    public function toArray()
    {
        return $this->values;
    }

    /** @return ArrayIterator<string, string|true> */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->values);
    }
}
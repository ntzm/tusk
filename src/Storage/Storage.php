<?php

namespace Tusk\Storage;

use Tusk\FileNotFound;

interface Storage
{
    /** @throws FileNotFound */
    public function getOffset(string $id): int;

    /**
     * @param resource $data
     *
     * @throws FileNotFound
     */
    public function append(string $id, $data): void;

    /** @throws FileNotFound */
    public function complete(string $id): void;

    public function create(string $id, int $length, ?string $metadata): void;

    /** @throws FileNotFound */
    public function delete(string $id): void;

    /** @throws FileNotFound */
    public function getLength(string $id): int;

    /** @throws FileNotFound */
    public function getMetaData(string $id): ?string;
}

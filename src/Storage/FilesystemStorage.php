<?php

namespace Tusk\Storage;

use Tusk\FileNotFound;

final class FilesystemStorage implements Storage
{
    /** @var string */
    private $directory;

    public function __construct(string $directory)
    {
        $this->directory = rtrim($directory, '/') . '/';
    }

    public function getOffset(string $id): int
    {
        set_error_handler(static function () {});
        $offset = filesize($this->directory . $id);
        restore_error_handler();

        if ($offset === false) {
            throw FileNotFound::withId($id);
        }

        return $offset;
    }

    public function append(string $id, $data): void
    {
        if (! file_exists($this->directory . $id)) {
            throw FileNotFound::withId($id);
        }

        set_error_handler(static function () {});
        $file = fopen($this->directory . $id, 'a');
        restore_error_handler();

        if ($file === false) {
            throw FileNotFound::withId($id);
        }

        stream_copy_to_stream($data, $file);
        fclose($file);

        // Otherwise getOffset will return the incorrect value
        clearstatcache(true, $this->directory . $id);
    }

    public function complete(string $id): void
    {
        unlink($this->directory . $id . '_meta.json');
    }

    public function create(string $id, int $length, ?string $metadata): void
    {
        touch($this->directory . $id);

        file_put_contents($this->directory . $id . '_meta.json', json_encode([
            'length' => $length,
            'metadata' => $metadata,
        ], JSON_THROW_ON_ERROR));
    }

    public function delete(string $id): void
    {
        if (! file_exists($this->directory . $id)) {
            throw FileNotFound::withId($id);
        }

        unlink($this->directory . $id);
        unlink($this->directory . $id . '_meta.json');
    }

    public function getLength(string $id): int
    {
        return $this->getFileMetadata($id)['length'];
    }

    public function getMetaData(string $id): ?string
    {
        return $this->getFileMetadata($id)['metadata'];
    }

    /**
     * @throws FileNotFound
     *
     * @return array{length: int, metadata: string|null}
     */
    private function getFileMetadata(string $id): array
    {
        set_error_handler(static function () {});
        $contents = file_get_contents($this->directory . $id . '_meta.json');
        restore_error_handler();

        if ($contents === false) {
            throw FileNotFound::withId($id);
        }

        /** @var array{length: int, metadata: string|null} $data */
        $data = json_decode($contents, true, JSON_THROW_ON_ERROR);

        return $data;
    }
}

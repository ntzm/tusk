<?php

namespace Tusk\Event;

final class UploadComplete
{
    /** @var string */
    private $fileId;

    public function __construct(string $fileId)
    {
        $this->fileId = $fileId;
    }

    public function fileId(): string
    {
        return $this->fileId;
    }
}

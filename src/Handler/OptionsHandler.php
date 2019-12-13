<?php

namespace Tusk\Handler;

use Psr\Http\Message\ResponseInterface;
use Tusk\Tus;

final class OptionsHandler
{
    /** @var int|null */
    private $maxFileSize;

    public function __construct(?int $maxFileSize)
    {
        $this->maxFileSize = $maxFileSize;
    }

    public function handle(ResponseInterface $response): ResponseInterface
    {
        $response = $response->withHeader('Tus-Resumable', Tus::VERSION);

        if ($this->maxFileSize !== null) {
            $response = $response->withHeader('Tus-Max-Size', (string) $this->maxFileSize);
        }

        return $response
            ->withHeader('Tus-Version', Tus::VERSION)
            ->withHeader('Tus-Extension', 'creation,termination')
            ->withStatus(204)
        ;
    }
}

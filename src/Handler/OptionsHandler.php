<?php

namespace Tusk\Handler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tusk\Tusk;

final class OptionsHandler
{
    /** @var int|null */
    private $maxFileSize;

    public function __construct(?int $maxFileSize)
    {
        $this->maxFileSize = $maxFileSize;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $response = $response->withHeader('Tus-Resumable', Tusk::TUS_VERSION);

        if ($this->maxFileSize !== null) {
            $response = $response->withHeader('Tus-Max-Size', (string) $this->maxFileSize);
        }

        return $response
            ->withHeader('Tus-Version', Tusk::TUS_VERSION)
            ->withHeader('Tus-Extension', 'creation,termination')
            ->withStatus(204)
        ;
    }
}

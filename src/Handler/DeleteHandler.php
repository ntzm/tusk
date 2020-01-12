<?php

namespace Tusk\Handler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tusk\FileNotFound;
use Tusk\Storage\Storage;
use Tusk\Tusk;

final class DeleteHandler
{
    /** @var Storage */
    private $storage;

    public function __construct(Storage $storage)
    {
        $this->storage = $storage;
    }

    public function handle(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $response = $response->withHeader('Tus-Resumable', Tusk::TUS_VERSION);

        if ($request->getHeaderLine('Tus-Resumable') !== Tusk::TUS_VERSION) {
            return $response->withStatus(412)->withHeader('Tus-Version', Tusk::TUS_VERSION);
        }

        $id = $request->getAttribute('id');

        if ($id === null) {
            return $response->withStatus(404);
        }

        try {
            $this->storage->delete($id);
        } catch (FileNotFound $e) {
            return $response->withStatus(404);
        }

        return $response->withStatus(204);
    }
}

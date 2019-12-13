<?php

namespace Tusk\Handler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tusk\FileNotFound;
use Tusk\Storage\Storage;
use Tusk\Tus;

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
        $response = $response->withHeader('Tus-Resumable', Tus::VERSION);

        if ($request->getHeaderLine('Tus-Resumable') !== Tus::VERSION) {
            return $response->withStatus(412)->withHeader('Tus-Version', Tus::VERSION);
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

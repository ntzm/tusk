<?php

namespace Tusk\Handler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tusk\FileNotFound;
use Tusk\Storage\Storage;
use Tusk\Tusk;

final class HeadHandler
{
    /** @var Storage */
    private $storage;

    public function __construct(Storage $storage)
    {
        $this->storage = $storage;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $response = $response->withHeader('Tus-Resumable', Tusk::TUS_VERSION);

        if ($request->getHeaderLine('Tus-Resumable') !== Tusk::TUS_VERSION) {
            return $response->withStatus(412)->withHeader('Tus-Version', Tusk::TUS_VERSION);
        }

        $response = $response->withHeader('Cache-Control', 'no-store');

        $id = $request->getAttribute('id');

        if ($id === null) {
            return $response->withStatus(404);
        }

        try {
            $offset = $this->storage->getOffset($id);
            $length = $this->storage->getLength($id);
            $metadata = $this->storage->getMetaData($id);
        } catch (FileNotFound $e) {
            return $response->withStatus(404);
        }

        if ($metadata !== null) {
            $response = $response->withHeader('Upload-Metadata', $metadata);
        }

        return $response
            ->withStatus(204)
            ->withHeader('Upload-Offset', (string) $offset)
            ->withHeader('Upload-Length', (string) $length)
        ;
    }
}

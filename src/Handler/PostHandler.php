<?php

namespace Tusk\Handler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tusk\IdGenerator\IdGenerator;
use Tusk\LocationGenerator\LocationGenerator;
use Tusk\Storage\Storage;
use Tusk\Tusk;

final class PostHandler
{
    /** @var Storage */
    private $storage;

    /** @var IdGenerator */
    private $idGenerator;

    /** @var int|null */
    private $maxFileSize;

    /** @var LocationGenerator */
    private $locationGenerator;

    public function __construct(
        Storage $storage,
        IdGenerator $idGenerator,
        ?int $maxFileSize,
        LocationGenerator $locationGenerator
    ) {
        $this->storage = $storage;
        $this->idGenerator = $idGenerator;
        $this->maxFileSize = $maxFileSize;
        $this->locationGenerator = $locationGenerator;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $response = $response->withHeader('Tus-Resumable', Tusk::TUS_VERSION);

        if ($request->getHeaderLine('Tus-Resumable') !== Tusk::TUS_VERSION) {
            return $response->withStatus(412)->withHeader('Tus-Version', Tusk::TUS_VERSION);
        }

        $length = (int) $request->getHeaderLine('Upload-Length');

        if ($this->maxFileSize !== null && $length > $this->maxFileSize) {
            return $response->withStatus(413);
        }

        $metadata = $request->getHeaderLine('Upload-Metadata') ?: null;

        $id = $this->idGenerator->generate();

        $this->storage->create($id, $length, $metadata);

        return $response
            ->withHeader('Location', $this->locationGenerator->generate($request, $id))
            ->withStatus(201)
        ;
    }
}

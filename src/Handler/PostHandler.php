<?php

namespace Tusk\Handler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tusk\IdGenerator\IdGenerator;
use Tusk\LocationGenerator\LocationGenerator;
use Tusk\Storage\Storage;
use Tusk\Tus;

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

    public function handle(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $response = $response->withHeader('Tus-Resumable', Tus::VERSION);

        if ($request->getHeaderLine('Tus-Resumable') !== Tus::VERSION) {
            return $response->withStatus(412)->withHeader('Tus-Version', Tus::VERSION);
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

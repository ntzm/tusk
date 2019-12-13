<?php

namespace Tusk;

use InvalidArgumentException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tusk\Handler\DeleteHandler;
use Tusk\Handler\HeadHandler;
use Tusk\Handler\OptionsHandler;
use Tusk\Handler\PatchHandler;
use Tusk\Handler\PostHandler;
use Tusk\IdGenerator\IdGenerator;
use Tusk\IdGenerator\RandomHexIdGenerator;
use Tusk\LocationGenerator\LocationGenerator;
use Tusk\LocationGenerator\RelativeLocationGenerator;
use Tusk\Storage\Storage;

final class Tus
{
    public const VERSION = '1.0.0';

    /** @var Storage */
    private $storage;

    /** @var int|null */
    private $maxFileSize;

    /** @var LocationGenerator */
    private $locationGenerator;

    /** @var IdGenerator */
    private $idGenerator;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    public function __construct(
        Storage $storage,
        ?int $maxFileSize = null,
        ?LocationGenerator $locationGenerator = null,
        ?IdGenerator $idGenerator = null,
        ?EventDispatcherInterface $eventDispatcher = null
    ) {
        if ($maxFileSize !== null && $maxFileSize < 0) {
            throw new InvalidArgumentException('Max file size must be a positive integer');
        }

        $this->storage = $storage;
        $this->maxFileSize = $maxFileSize;
        $this->locationGenerator = $locationGenerator ?? new RelativeLocationGenerator();
        $this->idGenerator = $idGenerator ?? new RandomHexIdGenerator();
        $this->eventDispatcher = $eventDispatcher ?? new NullEventDispatcher();
    }

    public function handle(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $method = $request->getMethod();

        if ($method === 'OPTIONS') {
            return (new OptionsHandler($this->maxFileSize))->handle($response);
        }

        if ($method === 'POST') {
            return (new PostHandler(
                $this->storage,
                $this->idGenerator,
                $this->maxFileSize,
                $this->locationGenerator
            ))->handle($request, $response);
        }

        if ($method === 'HEAD') {
            return (new HeadHandler($this->storage))->handle($request, $response);
        }

        if ($method === 'PATCH') {
            return (new PatchHandler($this->storage, $this->eventDispatcher))->handle($request, $response);
        }

        if ($method === 'DELETE') {
            return (new DeleteHandler($this->storage))->handle($request, $response);
        }

        return $response->withStatus(405);
    }
}

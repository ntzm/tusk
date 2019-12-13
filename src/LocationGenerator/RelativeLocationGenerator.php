<?php

namespace Tusk\LocationGenerator;

use Psr\Http\Message\ServerRequestInterface;

final class RelativeLocationGenerator implements LocationGenerator
{
    public function generate(ServerRequestInterface $request, string $id): string
    {
        return rtrim($request->getUri()->getPath(), '/') . '/' . $id;
    }
}

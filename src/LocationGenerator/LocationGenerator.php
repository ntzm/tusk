<?php

namespace Tusk\LocationGenerator;

use Psr\Http\Message\ServerRequestInterface;

interface LocationGenerator
{
    public function generate(ServerRequestInterface $request, string $id): string;
}

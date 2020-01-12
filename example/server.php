<?php

declare(strict_types=1);

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Factory\AppFactory;
use Tusk\Tusk;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);

$app->any('/files[/{id}]', function (ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
    $response = $response->withHeader('Access-Control-Allow-Origin', '*');
    $response = $response->withHeader('Access-Control-Allow-Headers', '*');
    $response = $response->withHeader('Access-Control-Expose-Headers', '*');
    $response = $response->withHeader('Access-Control-Allow-Methods', '*');

    $tus = new Tusk(
        new \Tusk\Storage\S3Storage(new \Aws\S3\S3Client([
            'version' => '2006-03-01',
            'region' => 'a',
            'credentials' => ['key' => 'a', 'secret' => 'a'],
            'endpoint' => 'http://localhost:8601',
            'use_path_style_endpoint' => true,
        ]), 'test')
    );

    return $tus->handle($request, $response);
});

$app->run();

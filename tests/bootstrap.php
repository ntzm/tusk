<?php

use Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__ . '/../', '.env.test');

$dotenv->safeLoad();

$dotenv->required([
    'S3_REGION',
    'S3_AUTH_KEY',
    'S3_AUTH_SECRET',
    'S3_BUCKET',
]);

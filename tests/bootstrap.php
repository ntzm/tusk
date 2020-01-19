<?php

use Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

$_ENV['S3_REGION'] = 'foo';
$_ENV['S3_AUTH_KEY'] = 'foo';
$_ENV['S3_AUTH_SECRET'] = 'foo';
$_ENV['S3_AUTH_BUCKET'] = 'foo';
$_ENV['S3_ENDPOINT'] = 'http://localhost:4572';

Dotenv::createImmutable(__DIR__ . '/../', '.env.test')->safeLoad();

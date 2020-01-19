<?php

use Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

putenv('S3_REGION=foo');
putenv('S3_AUTH_KEY=foo');
putenv('S3_AUTH_SECRET=foo');
putenv('S3_BUCKET=foo');
putenv('S3_ENDPOINT=http://localhost:4572');

Dotenv::createMutable(__DIR__ . '/../', '.env.test')->safeLoad();

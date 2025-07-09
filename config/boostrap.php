<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

if (!isset($_SERVER['APP_ENV']) && !isset($_ENV['APP_ENV'])) {
    (new Dotenv())->usePutenv()->bootEnv(dirname(__DIR__) . '/.env');
}

// APP_RUNTIME uniquement en production
if ($_ENV['APP_ENV'] === 'prod') {
    putenv('APP_RUNTIME=Runtime\\FrankenPhpSymfony\\Runtime');
}

<?php

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = new Dotenv\Dotenv(__DIR__ . '/../');
$dotenv->load();

$container = new Slim\Container();
$app = new Slim\App($container);

require __DIR__ . '/../app/container.php';
require __DIR__ . '/../app/routes.php';

$app->run();

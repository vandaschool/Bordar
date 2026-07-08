<?php

declare(strict_types=1);

/** @var \App\Core\Router $router */
$router = require dirname(__DIR__) . '/src/bootstrap.php';

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);

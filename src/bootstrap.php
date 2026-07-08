<?php

declare(strict_types=1);

use App\Config\App;
use App\Core\ErrorHandler;
use App\Core\Session;
use Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

date_default_timezone_set(App::timezone());

ErrorHandler::register();

mb_internal_encoding('UTF-8');

Session::start();

return require __DIR__ . '/routes.php';

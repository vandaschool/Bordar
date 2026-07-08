<?php

declare(strict_types=1);

namespace App\Core;

abstract class Middleware
{
    /**
     * Return false to halt the request (middleware is responsible for sending
     * a response/redirect in that case). Return true/null to continue.
     *
     * @param array<string, string> $routeParams
     */
    abstract public function handle(array $routeParams): bool;
}

<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var array<string, array<int, array{pattern: string, paramNames: array<int,string>, handler: mixed, middleware: array<int,string>}>> */
    private array $routes = [
        'GET' => [],
        'POST' => [],
        'PUT' => [],
        'PATCH' => [],
        'DELETE' => [],
    ];

    /** @var array<int, string> */
    private array $groupMiddleware = [];

    private string $groupPrefix = '';

    public function get(string $uri, mixed $handler, array $middleware = []): void
    {
        $this->addRoute('GET', $uri, $handler, $middleware);
    }

    public function post(string $uri, mixed $handler, array $middleware = []): void
    {
        $this->addRoute('POST', $uri, $handler, $middleware);
    }

    public function put(string $uri, mixed $handler, array $middleware = []): void
    {
        $this->addRoute('PUT', $uri, $handler, $middleware);
    }

    public function patch(string $uri, mixed $handler, array $middleware = []): void
    {
        $this->addRoute('PATCH', $uri, $handler, $middleware);
    }

    public function delete(string $uri, mixed $handler, array $middleware = []): void
    {
        $this->addRoute('DELETE', $uri, $handler, $middleware);
    }

    public function group(array $options, \Closure $callback): void
    {
        $previousPrefix = $this->groupPrefix;
        $previousMiddleware = $this->groupMiddleware;

        $this->groupPrefix .= $options['prefix'] ?? '';
        $this->groupMiddleware = array_merge($this->groupMiddleware, $options['middleware'] ?? []);

        $callback($this);

        $this->groupPrefix = $previousPrefix;
        $this->groupMiddleware = $previousMiddleware;
    }

    private function addRoute(string $method, string $uri, mixed $handler, array $middleware): void
    {
        $uri = $this->groupPrefix . $uri;
        $uri = '/' . trim($uri, '/');

        $paramNames = [];
        $pattern = preg_replace_callback('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', function (array $m) use (&$paramNames) {
            $paramNames[] = $m[1];

            return '([^/]+)';
        }, $uri);

        $this->routes[$method][] = [
            'pattern' => '#^' . $pattern . '$#',
            'paramNames' => $paramNames,
            'handler' => $handler,
            'middleware' => array_merge($this->groupMiddleware, $middleware),
        ];
    }

    public function dispatch(string $method, string $uri): mixed
    {
        $path = '/' . trim((string) parse_url($uri, PHP_URL_PATH), '/');
        $method = strtoupper($method);

        if ($method === 'POST' && isset($_POST['_method'])) {
            $override = strtoupper((string) $_POST['_method']);
            if (in_array($override, ['PUT', 'PATCH', 'DELETE'], true)) {
                $method = $override;
            }
        }

        foreach ($this->routes[$method] ?? [] as $route) {
            if (preg_match($route['pattern'], $path, $matches)) {
                array_shift($matches);
                $params = array_combine($route['paramNames'], $matches);

                foreach ($route['middleware'] as $middlewareClass) {
                    /** @var Middleware $middleware */
                    $middleware = new $middlewareClass();
                    $result = $middleware->handle($params);

                    if ($result === false) {
                        return null;
                    }
                }

                return $this->invoke($route['handler'], $params);
            }
        }

        http_response_code(404);
        $view = new View();
        echo $view->render('errors/404');

        return null;
    }

    private function invoke(mixed $handler, array $params): mixed
    {
        if ($handler instanceof \Closure) {
            return $handler(...array_values($params));
        }

        [$controllerClass, $action] = $handler;
        $controller = new $controllerClass();

        return $controller->$action(...array_values($params));
    }
}

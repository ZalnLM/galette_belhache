<?php
declare(strict_types=1);

class Router
{
    private array $routes = [];

    public function get(string $path, array $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, array $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    private function add(string $method, string $path, array $handler): void
    {
        $pattern = '#^/' . trim(preg_replace('#\{[^/]+\}#', '([^/]+)', trim($path, '/')), '/') . '$#';
        if ($path === '') {
            $pattern = '#^/$#';
        }

        $this->routes[$method][] = [
            'pattern' => $pattern,
            'handler' => $handler,
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        foreach ($this->routes[$method] ?? [] as $route) {
            if (preg_match($route['pattern'], $path, $matches)) {
                array_shift($matches);
                [$class, $action] = $route['handler'];
                $controller = new $class();
                $controller->{$action}(...$matches);
                return;
            }
        }

        http_response_code(404);
        echo 'Page introuvable.';
    }
}

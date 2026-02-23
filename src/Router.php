<?php
namespace App;

/**
 * Simple regex-based router.
 */
class Router
{
    /** @var array{method: string, pattern: string, handler: callable}[] */
    private array $routes = [];

    public function get(string $pattern, callable $handler): self
    {
        return $this->addRoute('GET', $pattern, $handler);
    }

    public function post(string $pattern, callable $handler): self
    {
        return $this->addRoute('POST', $pattern, $handler);
    }

    public function any(string $pattern, callable $handler): self
    {
        $this->addRoute('GET', $pattern, $handler);
        return $this->addRoute('POST', $pattern, $handler);
    }

    private function addRoute(string $method, string $pattern, callable $handler): self
    {
        $this->routes[] = [
            'method'  => $method,
            'pattern' => $pattern,
            'handler' => $handler,
        ];
        return $this;
    }

    /**
     * Dispatch the current request.
     */
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Remove trailing slash (except for root)
        if ($uri !== '/' && str_ends_with($uri, '/')) {
            $uri = rtrim($uri, '/');
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            // Convert route pattern to regex
            // e.g. /admin/entry/{id}/edit -> #^/admin/entry/(?P<id>[^/]+)/edit$#
            $regex = preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $route['pattern']);
            $regex = '#^' . $regex . '$#';

            if (preg_match($regex, $uri, $matches)) {
                // Extract named parameters
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                call_user_func($route['handler'], $params);
                return;
            }
        }

        // 404
        http_response_code(404);
        render('errors/404');
    }
}

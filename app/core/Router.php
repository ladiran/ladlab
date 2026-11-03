<?php
declare(strict_types=1);

final class Router
{
    private array $routes = [];

    public function get(string $pattern, array $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    public function post(string $pattern, array $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    private function add(string $method, string $pattern, array $handler): void
    {
        // {name} matches one path segment; {id:\d+} uses a custom pattern.
        $regex = preg_replace_callback('/\{(\w+)(?::([^}]+))?\}/', fn($m) => '(?P<' . $m[1] . '>' . ($m[2] ?? '[^/]+') . ')', $pattern);
        $this->routes[] = [$method, '#^' . $regex . '$#', $handler];
    }

    public function dispatch(string $method, string $path): void
    {
        $path = '/' . trim($path, '/');
        $pathMatched = false;
        foreach ($this->routes as [$m, $regex, $handler]) {
            if (!preg_match($regex, $path, $matches)) {
                continue;
            }
            $pathMatched = true;
            if ($m !== $method && !($method === 'HEAD' && $m === 'GET')) {
                continue;
            }
            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            [$class, $action] = $handler;
            (new $class())->$action(...array_values($params));
            return;
        }
        if ($pathMatched) {
            abort(405, 'That action is not allowed here.');
        }
        abort(404);
    }
}

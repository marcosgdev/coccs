<?php

namespace GestContratos\Core;

final class Router
{
    private array $routes = [];

    public function get(string $path, array|callable $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, array|callable $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    public function add(string $method, string $path, array|callable $handler): void
    {
        $pattern = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', '/' . trim($path, '/'));
        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => '#^' . $pattern . '$#',
            'handler' => $handler,
        ];
    }

    public function dispatch(Request $request): void
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->method) {
                continue;
            }
            if (! preg_match($route['pattern'], $request->path, $matches)) {
                continue;
            }
            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            $this->call($route['handler'], $request, $params);
            return;
        }

        http_response_code(404);
        View::render('errors/404', ['title' => 'Pagina nao encontrada']);
    }

    private function call(array|callable $handler, Request $request, array $params): void
    {
        if (is_callable($handler)) {
            $ref = new \ReflectionFunction(\Closure::fromCallable($handler));
            $handler(...$this->arguments($ref, $request, $params));
            return;
        }

        [$class, $method] = $handler;
        $controller = new $class();
        $ref = new \ReflectionMethod($controller, $method);
        $controller->{$method}(...$this->arguments($ref, $request, $params));
    }

    private function arguments(\ReflectionFunctionAbstract $ref, Request $request, array $params): array
    {
        $args = [];
        $parameters = $ref->getParameters();
        if ($parameters) {
            $first = $parameters[0];
            $type = $first->getType();
            if (($type instanceof \ReflectionNamedType && $type->getName() === Request::class) || $first->getName() === 'request') {
                $args[] = $request;
            }
        }
        return array_merge($args, array_values($params));
    }
}

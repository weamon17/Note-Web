<?php
declare(strict_types=1);

namespace App\Core;

class Router
{
    private array $routes   = [];
    private string $basePath = '';

    public function __construct()
    {
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
        if (!str_ends_with($scriptName, 'index.php')) {
            $scriptName = '/index.php';
        }
        $dir = rtrim(dirname($scriptName), '/\\');
        $this->basePath = ($dir === '/' || $dir === '\\') ? '' : $dir;
    }

    public function get(string $path, string $handler): static
    {
        $this->addRoute('GET', $path, $handler);
        return $this;
    }

    public function post(string $path, string $handler): static
    {
        $this->addRoute('POST', $path, $handler);
        return $this;
    }

    private function addRoute(string $method, string $path, string $handler): void
    {
        // Convert {param} to named capture groups
        $pattern = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', '(?P<$1>[^/]+)', $path);
        $pattern = '#^' . $pattern . '$#u';

        $this->routes[] = [
            'method'  => strtoupper($method),
            'pattern' => $pattern,
            'handler' => $handler,
        ];
    }

    public function dispatch(): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        // Support POST method override via _method field (for DELETE/PUT via forms)
        if ($method === 'POST' && !empty($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }

        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $uri = rawurldecode($uri);

        // Strip base path prefix
        if ($this->basePath !== '' && str_starts_with($uri, $this->basePath)) {
            $uri = substr($uri, strlen($this->basePath));
        }
        $uri = '/' . ltrim($uri, '/');

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            if (preg_match($route['pattern'], $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $this->callHandler($route['handler'], array_values($params));
                return;
            }
        }

        $this->respondNotFound();
    }

    private function callHandler(string $handler, array $params): void
    {
        [$classShort, $action] = explode('@', $handler, 2);

        // Support sub-namespaces separated by '/'  e.g. 'Api/NoteApiController'
        $fqn = 'App\\Controllers\\' . str_replace('/', '\\', $classShort);

        if (!class_exists($fqn)) {
            $this->respondNotFound();
            return;
        }

        $controller = new $fqn();

        if (!method_exists($controller, $action)) {
            $this->respondNotFound();
            return;
        }

        $controller->$action(...$params);
    }

    private function respondNotFound(): void
    {
        http_response_code(404);
        $errView = BASE_PATH . '/app/Views/errors/404.php';
        if (file_exists($errView)) {
            require $errView;
        } else {
            echo '<h1>404 – Page Not Found</h1>';
        }
    }
}

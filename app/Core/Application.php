<?php

namespace GestContratos\Core;

final class Application
{
    public function __construct(private readonly string $basePath)
    {
        $GLOBALS['base_path'] = $this->basePath;
        $this->loadEnv();

        date_default_timezone_set(config('app.timezone', 'America/Belem'));

        $sessionName = config('app.session_name', 'gestcontratos_session');
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_name($sessionName);
            session_set_cookie_params([
                'httponly' => true,
                'samesite' => 'Lax',
                'secure' => (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            ]);
            session_start();
        }
    }

    public function run(): void
    {
        try {
            $router = new Router();
            require $this->basePath . '/config/routes.php';
            $router->dispatch(Request::capture());
        } catch (\Throwable $exception) {
            http_response_code(500);
            $this->logException($exception);
            if (config('app.debug', false)) {
                echo '<pre>' . e($exception) . '</pre>';
                return;
            }
            echo 'Ocorreu um erro interno. Verifique os logs da aplicacao.';
        } finally {
            unset($_SESSION['_old']);
        }
    }

    private function loadEnv(): void
    {
        $envFile = $this->basePath . '/.env';
        if (! file_exists($envFile)) {
            return;
        }
        foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value, " \t\n\r\0\x0B\"'");
        }
    }

    private function logException(\Throwable $exception): void
    {
        $dir = $this->basePath . '/storage/logs';
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $message = sprintf(
            "[%s] %s\n%s\n\n",
            date('Y-m-d H:i:s'),
            $exception->getMessage(),
            $exception->getTraceAsString()
        );

        file_put_contents($dir . '/app.log', $message, FILE_APPEND);
    }
}

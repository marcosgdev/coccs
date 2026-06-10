<?php

use GestContratos\Core\Csrf;

if (! function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        $root = $GLOBALS['base_path'] ?? dirname(__DIR__, 2);
        return $path === '' ? $root : $root . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
    }
}

if (! function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        return $_ENV[$key] ?? $_SERVER[$key] ?? $default;
    }
}

if (! function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        static $configs = [];
        [$file, $item] = array_pad(explode('.', $key, 2), 2, null);
        if (! isset($configs[$file])) {
            $path = base_path("config/{$file}.php");
            $configs[$file] = file_exists($path) ? require $path : [];
        }
        return $item === null ? $configs[$file] : ($configs[$file][$item] ?? $default);
    }
}

if (! function_exists('url')) {
    function url(string $path = ''): string
    {
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        $base = rtrim(config('app.url', ''), '/');
        $path = '/' . ltrim($path, '/');
        return $base . ($path === '/' ? '' : $path);
    }
}

if (! function_exists('asset')) {
    function asset(string $path): string
    {
        return url('assets/' . ltrim($path, '/'));
    }
}

if (! function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (! function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(Csrf::token()) . '">';
    }
}

if (! function_exists('method_field')) {
    function method_field(string $method): string
    {
        return '<input type="hidden" name="_method" value="' . e(strtoupper($method)) . '">';
    }
}

if (! function_exists('redirect')) {
    function redirect(string $path): never
    {
        header('Location: ' . url($path));
        exit;
    }
}

if (! function_exists('flash')) {
    function flash(string $key, ?string $message = null): ?string
    {
        if ($message !== null) {
            $_SESSION['_flash'][$key] = $message;
            return null;
        }
        $value = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }
}

if (! function_exists('old')) {
    function old(string $key, mixed $default = ''): mixed
    {
        return $_SESSION['_old'][$key] ?? $default;
    }
}

if (! function_exists('money_br')) {
    function money_br(mixed $value): string
    {
        return 'R$ ' . number_format((float) $value, 2, ',', '.');
    }
}

if (! function_exists('date_br')) {
    function date_br(?string $date): string
    {
        if (! $date) {
            return '-';
        }
        try {
            return (new DateTime($date))->format('d/m/Y');
        } catch (Throwable) {
            return '-';
        }
    }
}

if (! function_exists('badge_class')) {
    function badge_class(?string $value): string
    {
        $value = mb_strtolower((string) $value);
        return match (true) {
            str_contains($value, 'vigente'), str_contains($value, 'dentro') => 'text-bg-success',
            str_contains($value, 'expirado'), str_contains($value, 'fora'), str_contains($value, 'emergencial') => 'text-bg-danger',
            str_contains($value, '30'), str_contains($value, '60'), str_contains($value, 'reajuste') => 'text-bg-warning',
            str_contains($value, 'indeterminado'), str_contains($value, 'sem') => 'text-bg-secondary',
            default => 'text-bg-primary',
        };
    }
}

if (! function_exists('str_slug')) {
    function str_slug(string $value): string
    {
        $value = iconv('UTF-8', 'ASCII//TRANSLIT', $value);
        $value = preg_replace('/[^A-Za-z0-9]+/', '-', (string) $value);
        return strtolower(trim((string) $value, '-'));
    }
}

<?php

declare(strict_types=1);

define('WEBBARU_ROOT', dirname(__DIR__));
define('LEGACY_ROOT', dirname(__DIR__, 2));

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

spl_autoload_register(static function (string $class): void {
    $prefix = 'WebBaru\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $relative = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($prefix)));
    $file = __DIR__ . DIRECTORY_SEPARATOR . $relative . '.php';

    if (is_file($file)) {
        require $file;
    }
});

function view(string $name, array $data = []): void
{
    extract($data, EXTR_SKIP);
    $viewFile = __DIR__ . '/Views/' . $name . '.php';
    $layout = __DIR__ . '/Views/layout.php';
    if (!is_file($viewFile)) {
        http_response_code(404);
        echo 'View tidak ditemukan';
        exit;
    }
    require $layout;
}

function backend_view(string $name, array $data = []): void
{
    extract($data, EXTR_SKIP);
    $backendContentFile = __DIR__ . '/Views/' . $name . '.php';
    $layout = __DIR__ . '/Views/backend_layout.php';
    if (!is_file($backendContentFile)) {
        http_response_code(404);
        echo 'Backend view tidak ditemukan';
        exit;
    }
    require $layout;
}

function app_settings(): array
{
    static $cache = null;
    if (is_array($cache)) {
        return $cache;
    }

    try {
        $stmt = \WebBaru\Database::pdo()->query(
            "SELECT nama_instansi, alamat_instansi, kabupaten, propinsi, kontak, email
             FROM setting
             LIMIT 1"
        );
        $row = $stmt ? $stmt->fetch() : false;
        $cache = is_array($row) ? $row : [];
    } catch (\Throwable $e) {
        $cache = [];
    }

    return $cache;
}

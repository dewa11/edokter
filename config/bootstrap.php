<?php

declare(strict_types=1);

$rootPath = dirname(__DIR__);

require_once $rootPath . '/flight/Flight.php';

spl_autoload_register(static function (string $class) use ($rootPath): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = $rootPath . '/app/' . str_replace('\\', '/', $relativeClass) . '.php';

    if (is_file($file)) {
        require_once $file;
    }
});

\App\Helpers\Env::load($rootPath . '/.env');

$appEnv = strtolower((string) \App\Helpers\Env::get('APP_ENV', 'production'));
$appDebug = in_array(strtolower((string) \App\Helpers\Env::get('APP_DEBUG', '0')), ['1', 'true', 'yes', 'on'], true);
$isProduction = $appEnv === 'production';

if ($isProduction && !$appDebug) {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED & ~E_NOTICE & ~E_USER_NOTICE);
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('log_errors', '1');

    $logDir = $rootPath . '/storage/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    ini_set('error_log', $logDir . '/php-error.log');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
}

if ($isProduction) {
    $secret = (string) \App\Helpers\Env::get('APP_SECRET', '');
    if ($secret === '' || $secret === 'edokter-secret-key') {
        throw new RuntimeException('APP_SECRET must be configured with a strong, unique value in production.');
    }
}

date_default_timezone_set((string) \App\Helpers\Env::get('APP_TIMEZONE', 'Asia/Makassar'));

if (session_status() === PHP_SESSION_NONE) {
    $isHttps =
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443) ||
        strtolower((string) \App\Helpers\Env::get('APP_SESSION_SECURE', '')) === 'true';

    $sessionSameSite = (string) \App\Helpers\Env::get('APP_SESSION_SAMESITE', 'Lax');
    if (!in_array($sessionSameSite, ['Lax', 'Strict', 'None'], true)) {
        $sessionSameSite = 'Lax';
    }

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => (string) \App\Helpers\Env::get('APP_SESSION_DOMAIN', ''),
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => $sessionSameSite,
    ]);

    session_name((string) \App\Helpers\Env::get('APP_SESSION_NAME', 'EDOKTERSESSID'));
    session_start();
}

if (PHP_SAPI !== 'cli' && !headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');

    if ($isProduction) {
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    }
}

Flight::set('app.root', $rootPath);
Flight::set('flight.views.path', $rootPath . '/app/Views');

require_once $rootPath . '/config/database.php';

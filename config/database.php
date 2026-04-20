<?php

declare(strict_types=1);

use App\Helpers\Env;
use flight\database\PdoWrapper;

$dbHost = (string) Env::get('DB_HOST', '127.0.0.1');
$dbPort = (string) Env::get('DB_PORT', '3306');
$dbName = (string) Env::get('DB_NAME', '');
$dbUser = (string) Env::get('DB_USERNAME', '');
$dbPass = (string) Env::get('DB_PASSWORD', '');

$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $dbHost, $dbPort, $dbName);
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $db = new PdoWrapper($dsn, $dbUser, $dbPass, $options);
    Flight::set('db', $db);
    Flight::set('db.connected', true);
} catch (Throwable $e) {
    Flight::set('db', null);
    Flight::set('db.connected', false);
    Flight::set('db.error', $e->getMessage());
}

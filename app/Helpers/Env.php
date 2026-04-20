<?php

declare(strict_types=1);

namespace App\Helpers;

final class Env
{
    /** @var array<string, string> */
    private static array $data = [];

    public static function load(string $envPath): void
    {
        if (!is_file($envPath) || !is_readable($envPath)) {
            return;
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
            $key = trim($key);
            $value = trim($value);

            if ($key == '') {
                continue;
            }

            $value = trim($value, "\"'");
            self::$data[$key] = $value;

            if (getenv($key) === false) {
                putenv($key . '=' . $value);
            }

            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        if (array_key_exists($key, self::$data)) {
            return self::$data[$key];
        }

        $value = getenv($key);
        if ($value !== false) {
            return (string) $value;
        }

        return $default;
    }
}

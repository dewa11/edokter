<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\UserModel;
use Flight;

final class App
{
    public static function basePath(): string
    {
        $cached = Flight::has('app.base_path') ? Flight::get('app.base_path') : null;
        if (is_string($cached)) {
            return $cached;
        }

        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $scriptDir = str_replace('\\', '/', dirname($scriptName));

        if ($scriptDir === '/' || $scriptDir === '.') {
            $scriptDir = '';
        }

        if (str_ends_with($scriptDir, '/public')) {
            $scriptDir = substr($scriptDir, 0, -7);
        }

        $basePath = rtrim($scriptDir, '/');
        Flight::set('app.base_path', $basePath);

        return $basePath;
    }

    private static function publicUrlPrefix(): string
    {
        $documentRoot = (string) ($_SERVER['DOCUMENT_ROOT'] ?? '');
        if ($documentRoot !== '') {
            $normalizedRoot = rtrim(str_replace('\\', '/', $documentRoot), '/');

            if (str_ends_with($normalizedRoot, '/public')) {
                return '';
            }

            return '/public';
        }

        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

        // If URL includes /public/index.php, app is served from project root.
        if (str_contains($scriptName, '/public/')) {
            return '/public';
        }

        // If not, app is served with DocumentRoot already pointing at /public.
        return '';
    }

    public static function routePath(string $path = '/'): string
    {
        $base = self::basePath();

        if ($path === '' || $path === '/') {
            return $base !== '' ? $base . '/' : '/';
        }

        return ($base !== '' ? $base : '') . '/' . ltrim($path, '/');
    }

    public static function url(string $path = '/'): string
    {
        return self::routePath($path);
    }

    public static function asset(string $path): string
    {
        $prefix = self::publicUrlPrefix();
        return self::routePath($prefix . '/' . ltrim($path, '/'));
    }

    public static function logoUrl(): string
    {
        $logoName = Env::get('APP_LOGO_FILE', 'logo-default.svg');
        $prefix = self::publicUrlPrefix();
        return self::routePath($prefix . '/setting/logo/' . ltrim((string) $logoName, '/'));
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function render(string $view, array $data = [], string $layout = 'layouts/main'): void
    {
        $data['basePath'] = self::basePath();
        $data['routePath'] = [self::class, 'routePath'];
        $data['assetPath'] = [self::class, 'asset'];
        $data['logoUrl'] = self::logoUrl();
        $data['isLoggedIn'] = Auth::check();
        $data['doctorId'] = Auth::doctorId();
        $data['doctorName'] = '';

        if ($data['isLoggedIn'] && $data['doctorId'] !== '') {
            $userModel = new UserModel();
            $name = $userModel->findDoctorNameByCode((string) $data['doctorId']);
            $data['doctorName'] = $name !== '' ? $name : (string) $data['doctorId'];
        }

        $content = Flight::view()->fetch($view, $data);
        $layoutData = array_merge($data, ['content' => $content]);

        Flight::view()->render($layout, $layoutData);
    }

    public static function redirect(string $path): void
    {
        $target = self::routePath($path);

        $base = '';
        try {
            $request = Flight::request();
            $base = rtrim((string) $request->base, '/');
        } catch (\Throwable $e) {
            $base = '';
        }

        if ($base !== '' && str_starts_with($target, $base . '/')) {
            $target = substr($target, strlen($base));
            if ($target === '') {
                $target = '/';
            }
        }

        Flight::redirect($target);
    }

    public static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}

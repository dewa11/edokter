<?php

declare(strict_types=1);

namespace App\Helpers;

final class Auth
{
    private const ROLE_DOCTOR = 'doctor';
    private const ROLE_IT = 'it';

    public static function check(): bool
    {
        return isset($_SESSION['ses_dokter']) && $_SESSION['ses_dokter'] !== '';
    }

    public static function doctorId(): string
    {
        if (!self::check()) {
            return '';
        }

        return Crypto::decrypt((string) $_SESSION['ses_dokter']);
    }

    public static function login(string $doctorId, string $role = self::ROLE_DOCTOR): void
    {
        session_regenerate_id(true);
        $_SESSION['ses_dokter'] = Crypto::encrypt($doctorId);
        $_SESSION['auth_user'] = $doctorId;
        $_SESSION['auth_role'] = in_array($role, [self::ROLE_DOCTOR, self::ROLE_IT], true)
            ? $role
            : self::ROLE_DOCTOR;
        $_SESSION['auth_login_at'] = (new \DateTimeImmutable('now'))->format('Y-m-d H:i:s');
    }

    public static function role(): string
    {
        $role = strtolower(trim((string) ($_SESSION['auth_role'] ?? self::ROLE_DOCTOR)));

        return in_array($role, [self::ROLE_DOCTOR, self::ROLE_IT], true)
            ? $role
            : self::ROLE_DOCTOR;
    }

    public static function isItUser(): bool
    {
        return self::check() && self::role() === self::ROLE_IT;
    }

    public static function loginTimestamp(): string
    {
        return trim((string) ($_SESSION['auth_login_at'] ?? ''));
    }

    public static function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }
}

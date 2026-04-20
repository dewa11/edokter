<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\App;
use App\Helpers\Auth;
use App\Helpers\Captcha;
use App\Helpers\Env;
use App\Models\UserModel;
use Flight;

final class AuthController
{
    public function loginPage(): void
    {
        if (Auth::check()) {
            App::redirect(Auth::isItUser() ? '/it/dashboard' : '/dashboard');
            return;
        }

        Captcha::issueCode();

        App::render('auth/login', [
            'title' => 'Login - Edokter',
            'error' => $_SESSION['flash_error'] ?? '',
            'oldUsername' => $_SESSION['flash_old_username'] ?? '',
        ], 'layouts/auth');

        unset($_SESSION['flash_error'], $_SESSION['flash_old_username']);
    }

    public function loginSubmit(): void
    {
        $request = Flight::request();
        $username = trim((string) ($request->data->username ?? ''));
        $password = trim((string) ($request->data->password ?? ''));
        $captcha = trim((string) ($request->data->captcha ?? ''));

        $_SESSION['flash_old_username'] = $username;

        if ($username === '' || $password === '' || $captcha === '') {
            $_SESSION['flash_error'] = 'Username, password, and captcha are required.';
            App::redirect('/login');
            return;
        }

        if (!Captcha::isValid($captcha)) {
            $_SESSION['flash_error'] = 'Captcha is invalid or expired.';
            App::redirect('/login');
            return;
        }

        $allowBypass = in_array(
            strtolower((string) Env::get('APP_ALLOW_BYPASS_LOGIN', 'false')),
            ['1', 'true', 'yes', 'on'],
            true
        );
        $bypassUsername = (string) Env::get('APP_BYPASS_USERNAME', '');
        $bypassPassword = (string) Env::get('APP_BYPASS_PASSWORD', '');
        $isBypass = $allowBypass
            && $bypassUsername !== ''
            && $bypassPassword !== ''
            && hash_equals($bypassUsername, $username)
            && hash_equals($bypassPassword, $password);
        $isDoctor = false;

        if (!$isBypass) {
            $userModel = new UserModel();
            $isDoctor = $userModel->canLoginAsDoctor($username, $password);
        }

        if (!$isBypass && !$isDoctor) {
            $_SESSION['flash_error'] = 'Credential is not valid.';
            App::redirect('/login');
            return;
        }

        $role = $isBypass ? 'it' : 'doctor';

        Auth::login($username, $role);
        unset($_SESSION['captcha_code'], $_SESSION['captcha_issued_at']);
        App::redirect($role === 'it' ? '/it/dashboard' : '/dashboard');
    }

    public function captchaImage(): void
    {
        $code = (string) ($_SESSION['captcha_code'] ?? Captcha::issueCode());
        Captcha::outputImage($code);
    }

    public function logout(): void
    {
        Auth::logout();
        App::redirect('/login');
    }
}

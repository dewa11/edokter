<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\ItDashboardController;
use App\Helpers\App;
use App\Helpers\Auth;

$authController = new AuthController();
$dashboardController = new DashboardController();
$itDashboardController = new ItDashboardController();

$guard = static function (callable $callback): callable {
    return static function () use ($callback): void {
        if (!Auth::check()) {
            App::redirect('/login');
            return;
        }

        $callback();
    };
};

$doctorGuard = static function (callable $callback) use ($guard): callable {
    return $guard(static function () use ($callback): void {
        if (Auth::isItUser()) {
            App::redirect('/it/dashboard');
            return;
        }

        $callback();
    });
};

$itGuard = static function (callable $callback) use ($guard): callable {
    return $guard(static function () use ($callback): void {
        if (!Auth::isItUser()) {
            App::redirect('/dashboard');
            return;
        }

        $callback();
    });
};

$homeAction = static function (): void {
    if (Auth::check()) {
        App::redirect(Auth::isItUser() ? '/it/dashboard' : '/dashboard');
        return;
    }

    App::redirect('/login');
};

Flight::route('/', $homeAction);

Flight::route('/login', [$authController, 'loginPage']);
Flight::post('/login', [$authController, 'loginSubmit']);
Flight::route('/captcha', [$authController, 'captchaImage']);
Flight::route('/logout', [$authController, 'logout']);

Flight::route('/dashboard', $doctorGuard([$dashboardController, 'dashboard']));
Flight::route('/poli', $doctorGuard([$dashboardController, 'poli']));
Flight::route('/riwayat-perawatan', $guard([$dashboardController, 'riwayatPerawatan']));
Flight::route('/poli/export/excel', $doctorGuard([$dashboardController, 'poliExportExcel']));
Flight::route('/poli/export/html', $doctorGuard([$dashboardController, 'poliExportHtml']));
Flight::route('/rawat-inap', $doctorGuard([$dashboardController, 'rawatInap']));
Flight::route('/rawat-inap/resume', $doctorGuard([$dashboardController, 'rawatInapResume']));
Flight::post('/rawat-inap/resume', $doctorGuard([$dashboardController, 'rawatInapResumeSave']));
Flight::route('/rawat-inap/resume/autofill', $doctorGuard([$dashboardController, 'rawatInapResumeAutofill']));
Flight::route('/rawat-inap/resume/medicine-options', $doctorGuard([$dashboardController, 'rawatInapResumeMedicineOptions']));

Flight::route('/it/dashboard', $itGuard([$itDashboardController, 'dashboard']));
Flight::route('/it/rawat-inap', $itGuard([$itDashboardController, 'rawatInap']));
Flight::route('/it/rawat-inap/resume', $itGuard([$itDashboardController, 'rawatInapResume']));
Flight::post('/it/rawat-inap/resume', $itGuard([$itDashboardController, 'rawatInapResumeSave']));
Flight::route('/it/rawat-inap/resume/autofill', $itGuard([$itDashboardController, 'rawatInapResumeAutofill']));
Flight::route('/it/rawat-inap/resume/medicine-options', $itGuard([$itDashboardController, 'rawatInapResumeMedicineOptions']));

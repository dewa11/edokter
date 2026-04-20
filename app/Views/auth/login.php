<div class="container auth-container">
    <div class="row justify-content-center">
        <div class="col-12 col-sm-10 col-md-7 col-lg-5">
            <div class="card auth-card shadow-sm">
                <div class="card-body p-4 p-lg-5">
                    <div class="text-center mb-4">
                        <img src="<?= \App\Helpers\App::e((string) $logoUrl) ?>" alt="Logo" class="login-logo mb-2">
                        <h1 class="h4 mb-1">RSU Thalia Irham</h1>
                        <p class="text-secondary mb-0">Aplikasi eDokter, gunakan akun SIMRS anda untuk Login</p>
                    </div>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?= \App\Helpers\App::e((string) $error) ?></div>
                    <?php endif; ?>

                    <form method="post" action="<?= \App\Helpers\App::e((string) call_user_func($routePath, '/login')) ?>" autocomplete="off">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" value="<?= \App\Helpers\App::e((string) ($oldUsername ?? '')) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>

                        <div class="mb-3">
                            <label for="captcha" class="form-label">Captcha 4 Digit</label>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <img
                                    src="<?= \App\Helpers\App::e((string) call_user_func($routePath, '/captcha')) ?>?t=<?= time() ?>"
                                    data-captcha-src="<?= \App\Helpers\App::e((string) call_user_func($routePath, '/captcha')) ?>"
                                    alt="Captcha"
                                    class="captcha-image"
                                    id="captchaImage"
                                >
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="refreshCaptcha">Refresh</button>
                            </div>
                            <input type="text" class="form-control" id="captcha" name="captcha" maxlength="4" pattern="\d{4}" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Masuk</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

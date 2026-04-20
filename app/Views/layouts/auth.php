<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= \App\Helpers\App::e((string) ($title ?? 'Login - Edokter')) ?></title>
    <link rel="icon" type="image/png" href="<?= \App\Helpers\App::e((string) call_user_func($assetPath, 'setting/logo/logo.png')) ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= \App\Helpers\App::e((string) call_user_func($assetPath, 'assets/css/app.css')) ?>" rel="stylesheet">
    <link href="<?= \App\Helpers\App::e((string) call_user_func($assetPath, 'assets/css/auth.css')) ?>" rel="stylesheet">
</head>
<body class="auth-body">
    <?= $content ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= \App\Helpers\App::e((string) call_user_func($assetPath, 'assets/js/captcha.js')) ?>"></script>
</body>
</html>

# Production Deployment Checklist

## 1. Server requirements

- PHP 8.1+ recommended (minimum 7.4 based on current codebase)
- PHP extensions: `pdo_mysql`, `openssl`, `json`, `mbstring`
- Web server: Apache or Nginx
- HTTPS certificate installed and active

## 2. Project setup

1. Copy `.env.example` to `.env`.
2. Fill all production values, especially:
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `APP_SECRET` (strong random string)
   - Database credentials
3. Configure IT login credential via env vars:
   - `APP_ALLOW_BYPASS_LOGIN=true`
   - `APP_BYPASS_USERNAME=sysadmin`
   - `APP_BYPASS_PASSWORD=Tenreng85@`
   - Set `APP_ALLOW_BYPASS_LOGIN=false` if this fallback IT login must be disabled.

## 3. Web root and rewrite

- Point document root to `public/`.
- Enable rewrite rules from `public/.htaccess`.
- For Nginx, route non-existing paths to `public/index.php`.
- Keep root `.htaccess` only if you intentionally serve from project root (not recommended for production).

## 4. File permissions

- Ensure web user can write to:
  - `storage/`
  - `storage/logs/`
- Recommended ownership: web server user/group.
- Recommended directory permissions: 755 (or stricter according to your policy).

## 5. Security and runtime

- Run behind HTTPS.
- Disable directory listing on server.
- Keep `display_errors` disabled in production.
- Restrict access to `.env` from web.
- Rotate `APP_SECRET` if leaked.

## 6. Verify before go-live

1. Open login page and verify assets load.
2. Test valid and invalid login.
3. Test dashboard and critical features:
   - Poli
   - Riwayat Perawatan
   - Rawat Inap and resume save
4. Confirm PHP errors are logged in `storage/logs/php-error.log`.

## 7. Post-deploy operations

- Back up database regularly.
- Monitor error logs and access logs.
- Apply OS/PHP security updates routinely.

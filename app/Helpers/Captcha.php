<?php

declare(strict_types=1);

namespace App\Helpers;

final class Captcha
{
    public static function issueCode(): string
    {
        $code = (string) random_int(1000, 9999);
        $_SESSION['captcha_code'] = $code;
        $_SESSION['captcha_issued_at'] = time();

        return $code;
    }

    public static function isValid(string $input): bool
    {
        $expected = (string) ($_SESSION['captcha_code'] ?? '');
        $issuedAt = (int) ($_SESSION['captcha_issued_at'] ?? 0);

        if ($expected === '' || $issuedAt === 0) {
            return false;
        }

        if ((time() - $issuedAt) > 300) {
            unset($_SESSION['captcha_code'], $_SESSION['captcha_issued_at']);
            return false;
        }

        return hash_equals($expected, trim($input));
    }

    public static function outputImage(string $code): void
    {
        if (function_exists('imagecreatetruecolor')) {
            self::renderPng($code);
            return;
        }

        self::renderSvg($code);
    }

    private static function renderPng(string $code): void
    {
        $width = 150;
        $height = 52;
        $image = imagecreatetruecolor($width, $height);

        if ($image === false) {
            self::renderSvg($code);
            return;
        }

        $bg = imagecolorallocate($image, 230, 241, 255);
        $fg = imagecolorallocate($image, 12, 52, 131);
        $line = imagecolorallocate($image, 90, 145, 227);

        imagefilledrectangle($image, 0, 0, $width, $height, $bg);

        for ($i = 0; $i < 5; $i++) {
            imageline(
                $image,
                random_int(0, $width),
                random_int(0, $height),
                random_int(0, $width),
                random_int(0, $height),
                $line
            );
        }

        imagestring($image, 5, 45, 18, $code, $fg);

        header('Content-Type: image/png');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        imagepng($image);
        imagedestroy($image);
    }

    private static function renderSvg(string $code): void
    {
        $safeCode = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="150" height="52" viewBox="0 0 150 52">'
            . '<rect width="150" height="52" fill="#e6f1ff"/>'
            . '<line x1="4" y1="45" x2="145" y2="8" stroke="#5a91e3" stroke-width="1"/>'
            . '<line x1="10" y1="8" x2="140" y2="42" stroke="#9ec0f2" stroke-width="1"/>'
            . '<text x="40" y="34" font-size="26" font-family="monospace" letter-spacing="3" fill="#0c3483">'
            . $safeCode
            . '</text></svg>';

        header('Content-Type: image/svg+xml');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        echo $svg;
    }
}

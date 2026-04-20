<?php

declare(strict_types=1);

namespace App\Helpers;

final class Crypto
{
    public static function encrypt(string $plainText): string
    {
        $secret = Env::get('APP_SECRET', 'edokter-secret-key');
        $iv = substr(hash('sha256', (string) $secret), 0, 16);

        $cipherText = openssl_encrypt($plainText, 'AES-256-CBC', (string) $secret, 0, $iv);
        if ($cipherText === false) {
            return base64_encode($plainText);
        }

        return base64_encode($cipherText);
    }

    public static function decrypt(string $encryptedText): string
    {
        $secret = Env::get('APP_SECRET', 'edokter-secret-key');
        $iv = substr(hash('sha256', (string) $secret), 0, 16);
        $decoded = base64_decode($encryptedText, true);

        if ($decoded === false) {
            return '';
        }

        $plainText = openssl_decrypt($decoded, 'AES-256-CBC', (string) $secret, 0, $iv);

        if ($plainText === false) {
            $fallback = base64_decode($encryptedText, true);
            return $fallback === false ? '' : $fallback;
        }

        return $plainText;
    }
}

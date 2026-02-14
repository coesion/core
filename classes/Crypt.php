<?php

/**
 * Crypt
 *
 * Symmetric encryption using libsodium or OpenSSL.
 *
 * @package core
 * @author Stefano Azzolini <lastguest@gmail.com>
 * @copyright Coesion - 2026
 */

class Crypt {
    use Module;

    /**
     * Encrypt data with a secret key.
     *
     * @param string $data The plaintext data
     * @param string $key The encryption key (use Crypt::key() to generate)
     * @return string Base64-encoded ciphertext with nonce prepended
     */
    public static function encrypt($data, $key) {
        $key = static::normalizeKey($key);

        if (function_exists('sodium_crypto_secretbox')) {
            $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
            $cipher = sodium_crypto_secretbox($data, $nonce, $key);
            return base64_encode($nonce . $cipher);
        }

        if (function_exists('openssl_encrypt')) {
            $method = 'aes-256-gcm';
            $ivlen = openssl_cipher_iv_length($method);
            $iv = random_bytes($ivlen);
            $tag = '';
            $cipher = openssl_encrypt($data, $method, $key, OPENSSL_RAW_DATA, $iv, $tag, '', 16);
            return base64_encode($iv . $tag . $cipher);
        }

        throw new \RuntimeException('Crypt requires sodium or openssl extension');
    }

    /**
     * Decrypt data with a secret key.
     *
     * @param string $ciphertext Base64-encoded ciphertext from Crypt::encrypt()
     * @param string $key The same key used for encryption
     * @return string|false The decrypted plaintext or false on failure
     */
    public static function decrypt($ciphertext, $key) {
        $key = static::normalizeKey($key);
        $raw = base64_decode($ciphertext, true);
        if ($raw === false) return false;

        if (function_exists('sodium_crypto_secretbox_open')) {
            if (strlen($raw) < SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES) {
                return false;
            }
            $nonce = substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
            $cipher = substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
            $plain = sodium_crypto_secretbox_open($cipher, $nonce, $key);
            return $plain === false ? false : $plain;
        }

        if (function_exists('openssl_decrypt')) {
            $method = 'aes-256-gcm';
            $ivlen = openssl_cipher_iv_length($method);
            $taglen = 16;
            if (strlen($raw) < $ivlen + $taglen) return false;
            $iv = substr($raw, 0, $ivlen);
            $tag = substr($raw, $ivlen, $taglen);
            $cipher = substr($raw, $ivlen + $taglen);
            $plain = openssl_decrypt($cipher, $method, $key, OPENSSL_RAW_DATA, $iv, $tag);
            return $plain === false ? false : $plain;
        }

        throw new \RuntimeException('Crypt requires sodium or openssl extension');
    }

    /**
     * Generate a new random encryption key.
     *
     * @return string Hex-encoded key string
     */
    public static function key() {
        if (function_exists('sodium_crypto_secretbox_keygen')) {
            return bin2hex(sodium_crypto_secretbox_keygen());
        }
        return bin2hex(random_bytes(32));
    }

    /**
     * Check if encryption is available.
     *
     * @return bool
     */
    public static function available() {
        return function_exists('sodium_crypto_secretbox') || function_exists('openssl_encrypt');
    }

    /**
     * Normalize a hex key to binary, padded/trimmed to the required length.
     *
     * @param string $key
     * @return string Binary key
     */
    protected static function normalizeKey($key) {
        if (ctype_xdigit($key) && strlen($key) >= 32) {
            $key = hex2bin(substr($key, 0, 64));
        }
        $required = function_exists('sodium_crypto_secretbox')
            ? SODIUM_CRYPTO_SECRETBOX_KEYBYTES
            : 32;
        $key = str_pad(substr($key, 0, $required), $required, "\0");
        return $key;
    }
}

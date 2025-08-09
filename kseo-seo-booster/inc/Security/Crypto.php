<?php
/**
 * Simple crypto helper for encrypting sensitive data at rest
 *
 * @package KSEO\SEO_Booster\Security
 */

namespace KSEO\SEO_Booster\Security;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Crypto {
    /**
     * Derive a stable 32-byte key from WP salts
     */
    private static function deriveKey(): string {
        $material = (defined('AUTH_KEY') ? AUTH_KEY : '') . '|' . (defined('SECURE_AUTH_SALT') ? SECURE_AUTH_SALT : '');
        return hash('sha256', $material, true);
    }

    /**
     * Envelope encrypt: returns structured payload base64(version|key_id|nonce|tag|cipher)
     */
    public static function encrypt(string $plaintext): string {
        list($keyId, $key) = \KSEO\SEO_Booster\Security\Keyring::getActiveKey();
        $version = "v1";

        if (function_exists('sodium_crypto_aead_xchacha20poly1305_ietf_encrypt')) {
            $nonce = random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);
            $aad = $version . '|' . $keyId;
            $cipher = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt($plaintext, $aad, $nonce, $key);
            // AEAD returns cipher|tag combined; split not required; store tag empty for uniformity
            $tag = '';
            $payload = $version . '|' . $keyId . '|' . base64_encode($nonce) . '|' . base64_encode($tag) . '|' . base64_encode($cipher);
            return base64_encode($payload);
        }

        // Fallback: AES-256-GCM
        $iv = random_bytes(12);
        $tag = '';
        $cipher = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, $version . '|' . $keyId);
        $payload = $version . '|' . $keyId . '|' . base64_encode($iv) . '|' . base64_encode($tag) . '|' . base64_encode($cipher);
        return base64_encode($payload);
    }

    /**
     * Envelope decrypt for payloads created by encrypt(); returns '' on failure
     */
    public static function decrypt(string $b64): string {
        $outer = base64_decode($b64, true);
        if ($outer === false) { return ''; }
        $parts = explode('|', $outer);
        if (count($parts) !== 5) { return ''; }
        list($version, $keyId, $nonce_b64, $tag_b64, $cipher_b64) = $parts;
        $key = \KSEO\SEO_Booster\Security\Keyring::getKeyById($keyId);
        if ($key === '') { list(, $key) = \KSEO\SEO_Booster\Security\Keyring::getActiveKey(); }

        $nonce = base64_decode($nonce_b64, true) ?: '';
        $tag = base64_decode($tag_b64, true) ?: '';
        $cipher = base64_decode($cipher_b64, true);
        if ($cipher === false) { return ''; }

        $aad = $version . '|' . $keyId;
        if ($version === 'v1' && function_exists('sodium_crypto_aead_xchacha20poly1305_ietf_decrypt') && $nonce !== '') {
            $plain = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt($cipher, $aad, $nonce, $key);
            return $plain === false ? '' : $plain;
        }

        if ($nonce !== '') {
            $plain = openssl_decrypt($cipher, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag, $aad);
            return $plain === false ? '' : $plain;
        }
        return '';
    }
}


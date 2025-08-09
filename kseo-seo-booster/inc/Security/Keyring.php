<?php
/**
 * Keyring for envelope encryption
 *
 * @package KSEO\SEO_Booster\Security
 */

namespace KSEO\SEO_Booster\Security;

if (!defined('ABSPATH')) { exit; }

class Keyring {
    /**
     * Return active key id and binary key material
     * - Reads from constants: KSEO_ACTIVE_KEY_ID, KSEO_APP_KEY_{id} (base64)
     * - Fallback to derived key from WP salts (dev only)
     */
    public static function getActiveKey(): array {
        $activeId = defined('KSEO_ACTIVE_KEY_ID') ? (string) KSEO_ACTIVE_KEY_ID : 'dev';
        $key = self::getKeyById($activeId);
        if ($key === '') {
            // dev fallback
            $material = (defined('AUTH_KEY') ? AUTH_KEY : '') . '|' . (defined('SECURE_AUTH_SALT') ? SECURE_AUTH_SALT : '');
            $key = hash('sha256', $material, true);
            $activeId = 'dev';
        }
        return array($activeId, $key);
    }

    /**
     * Fetch key material by id
     */
    public static function getKeyById(string $keyId): string {
        $constName = 'KSEO_APP_KEY_' . strtoupper($keyId);
        if (defined($constName)) {
            $b64 = constant($constName);
            $bin = base64_decode($b64, true);
            return $bin !== false ? $bin : '';
        }
        return '';
    }
}


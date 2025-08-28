<?php
/**
 * Security helpers for KE SEO Booster Pro
 *
 * @package KSEO\SEO_Booster\Core
 */

namespace KSEO\SEO_Booster\Core;

if (!defined('ABSPATH')) { exit; }

class Security {
    /**
     * Per-user rate limit using transients (user-scoped)
     *
     * @param string $key Unique operation key (e.g., route name)
     * @param int $max Maximum allowed within window
     * @param int $window Window in seconds
     * @return array [$allowed(bool), $remaining(int), $retryAfter(int)]
     */
    public static function rate_limit(string $key, int $max, int $window): array {
        $userPart = is_user_logged_in() ? 'u:' . get_current_user_id() : 'ip:' . (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0');
        $bucket = (int) floor(time() / max(1, $window));
        $tkey = 'kseo:rl:' . md5($userPart . '|' . $key . '|' . $bucket);
        $count = (int) get_transient($tkey);
        $count++;
        $ttl = ($bucket + 1) * $window - time();
        if ($ttl < 1) { $ttl = 1; }
        set_transient($tkey, $count, $ttl);
        $allowed = $count <= $max;
        $remaining = $allowed ? ($max - $count) : 0;
        $retryAfter = $allowed ? 0 : $ttl;
        return array($allowed, $remaining, $retryAfter);
    }

    /**
     * Verify REST nonce and capability. Returns WP_Error on failure.
     *
     * @param string $cap Capability required
     * @param \WP_REST_Request $request Request
     * @return true|\WP_Error
     */
    public static function verify_rest(string $cap, \WP_REST_Request $request) {
        $nonce = $request->get_param('_wpnonce') ?: $request->get_header('x-wp-nonce');
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('forbidden', 'Invalid nonce', array('status' => 403));
        }
        if (!current_user_can($cap)) {
            return new \WP_Error('forbidden', 'Insufficient permissions', array('status' => 403));
        }
        return true;
    }
}



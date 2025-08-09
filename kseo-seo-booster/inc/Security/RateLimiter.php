<?php
/**
 * Redis-backed rate limiter with transient fallback
 *
 * @package KSEO\SEO_Booster\Security
 */

namespace KSEO\SEO_Booster\Security;

if (!defined('ABSPATH')) { exit; }

class RateLimiter {
    /**
     * Check allowance for a route+actor with limit per minute.
     * Returns array [allowed(bool), retryAfter(int seconds), headers(array)]
     */
    public static function check(string $route, string $actor, int $limitPerMin): array {
        // Allow per-route overrides
        $limits = get_option('kseo_rate_limits', array());
        if (isset($limits[$route])) {
            $limitPerMin = (int) $limits[$route];
        }
        $now = time();
        $bucket = gmdate('YmdHi', $now);
        $key = 'kseo:rl:' . md5($route . '|' . $actor . '|' . $bucket);

        // Try object cache/Redis
        if (function_exists('wp_cache_add') && function_exists('wp_cache_incr')) {
            if (!wp_cache_add($key, 0, '', 70)) {
                // key exists
            }
            $count = (int) wp_cache_incr($key);
            $ttl = (int) wp_cache_get($key . ':ttl');
            if (!$ttl) {
                $ttl = 60 - (int) gmdate('s', $now);
                wp_cache_set($key . ':ttl', $ttl, '', $ttl);
            }
            $allowed = $count <= $limitPerMin;
            return array($allowed, $allowed ? 0 : $ttl, self::headers($limitPerMin, max(0, $limitPerMin - $count), $ttl));
        }

        // Fallback: transient
        $count = (int) get_transient($key);
        $count++;
        $ttl = 60 - (int) gmdate('s', $now);
        set_transient($key, $count, $ttl);
        $allowed = $count <= $limitPerMin;
        return array($allowed, $allowed ? 0 : $ttl, self::headers($limitPerMin, max(0, $limitPerMin - $count), $ttl));
    }

    private static function headers(int $limit, int $remaining, int $retryAfter): array {
        return array(
            'X-RateLimit-Limit' => (string) $limit,
            'X-RateLimit-Remaining' => (string) $remaining,
            'Retry-After' => (string) $retryAfter,
        );
    }
}


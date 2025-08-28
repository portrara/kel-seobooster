<?php
/**
 * Cache helper using object cache with transient fallback
 *
 * @package KSEO\SEO_Booster\Core
 */

namespace KSEO\SEO_Booster\Core;

if (!defined('ABSPATH')) { exit; }

class Cache {
    public static function get(string $key) {
        if (function_exists('wp_cache_get')) {
            $v = wp_cache_get($key, 'kseo');
            if ($v !== false) { return $v; }
        }
        $v = get_transient($key);
        return $v === false ? null : $v;
    }

    public static function set(string $key, $value, int $ttl): void {
        if (function_exists('wp_cache_set')) {
            wp_cache_set($key, $value, 'kseo', $ttl);
        }
        set_transient($key, $value, $ttl);
    }

    public static function remember(string $key, int $ttl, callable $callback) {
        $cached = self::get($key);
        if ($cached !== null) { return $cached; }
        $value = call_user_func($callback);
        self::set($key, $value, $ttl);
        return $value;
    }
}



<?php
/**
 * Feature flags helper
 *
 * @package KSEO\SEO_Booster\Support
 */

namespace KSEO\SEO_Booster\Support;

if (!defined('ABSPATH')) { exit; }

class Flags {
    public static function all(): array {
        $defaults = array(
            'rate_limit_enabled' => true,
            'strict_json_validation' => true,
            'bearer_only' => true,
        );
        $opts = get_option('kseo_feature_flags', array());
        return array_merge($defaults, is_array($opts) ? $opts : array());
    }

    public static function is_enabled(string $flag): bool {
        $flags = self::all();
        return !empty($flags[$flag]);
    }

    public static function get(string $flag, $default = null) {
        $flags = self::all();
        return array_key_exists($flag, $flags) ? $flags[$flag] : $default;
    }
}


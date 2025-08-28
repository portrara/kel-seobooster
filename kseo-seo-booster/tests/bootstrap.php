<?php
// Minimal bootstrap to load classes without WordPress runtime
spl_autoload_register(function ($class) {
    if (strpos($class, 'KSEO\\SEO_Booster\\') === 0) {
        $path = __DIR__ . '/../inc/' . str_replace('KSEO\\SEO_Booster\\', '', $class) . '.php';
        $path = str_replace('\\', '/', $path);
        if (file_exists($path)) { require_once $path; }
    }
});

// Shim a couple of WP functions used in pure helpers
if (!function_exists('wp_json_encode')) { function wp_json_encode($data){ return json_encode($data); } }
if (!function_exists('sanitize_text_field')) { function sanitize_text_field($s){ return is_string($s) ? trim($s) : $s; } }
if (!function_exists('esc_url_raw')) { function esc_url_raw($s){ return $s; } }
if (!function_exists('sanitize_email')) { function sanitize_email($s){ return $s; } }
if (!function_exists('wp_strip_all_tags')) { function wp_strip_all_tags($s){ return strip_tags($s); } }



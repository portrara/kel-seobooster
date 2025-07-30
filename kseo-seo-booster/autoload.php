<?php
/**
 * PSR-4 Autoloader for KE SEO Booster Pro
 * 
 * @package KSEO\SEO_Booster
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

spl_autoload_register(function ($class) {
    // KSEO namespace prefix
    $prefix = 'KSEO\\SEO_Booster\\';
    
    // Base directory for the namespace prefix
    $base_dir = KSEO_PLUGIN_DIR . 'inc/';
    
    // Check if the class uses the namespace prefix
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // No, move to the next registered autoloader
        return;
    }
    
    // Get the relative class name
    $relative_class = substr($class, $len);
    
    // Replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    // If the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
}); 
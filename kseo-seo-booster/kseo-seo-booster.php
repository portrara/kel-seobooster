<?php
/**
 * Plugin Name: KE SEO Booster Pro
 * Plugin URI: https://github.com/portrara/kel-seobooster
 * Description: Full-featured, AI-powered WordPress SEO plugin with OpenAI integration, Google Keyword Planner, and comprehensive SEO tools.
 * Version: 2.0.0
 * Author: Krish Yadav
 * Author URI: https://github.com/portrara
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: kseo-seo-booster
 * Domain Path: /languages
 * Requires at least: 6.0
 * Tested up to: 6.4
 * Requires PHP: 8.0
 * Network: false
 * 
 * @package KSEO\SEO_Booster
 * @version 2.0.0
 * @author Krish Yadav
 * @license GPL v2 or later
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('KSEO_PLUGIN_FILE', __FILE__);
define('KSEO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('KSEO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('KSEO_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('KSEO_VERSION', '2.0.0');
define('KSEO_MIN_WP_VERSION', '6.0');
define('KSEO_MIN_PHP_VERSION', '8.0');

// Enhanced error handling function
function kseo_handle_error($error_message, $error_type = 'error') {
    error_log('KE SEO Booster Pro Error: ' . $error_message);
    
    if (is_admin()) {
        $notice_class = ($error_type === 'error') ? 'notice-error' : 'notice-warning';
        add_action('admin_notices', function() use ($error_message, $notice_class) {
            echo '<div class="notice ' . $notice_class . '"><p><strong>KE SEO Booster Pro:</strong> ' . esc_html($error_message) . '</p></div>';
        });
    }
}

// Check WordPress and PHP version compatibility with enhanced error handling
if (version_compare(get_bloginfo('version'), KSEO_MIN_WP_VERSION, '<')) {
    kseo_handle_error('Requires WordPress ' . KSEO_MIN_WP_VERSION . ' or higher. Current version: ' . get_bloginfo('version'));
    return;
}

if (version_compare(PHP_VERSION, KSEO_MIN_PHP_VERSION, '<')) {
    kseo_handle_error('Requires PHP ' . KSEO_MIN_PHP_VERSION . ' or higher. Current version: ' . PHP_VERSION);
    return;
}

// Check for required files before loading
$required_files = array(
    'autoload.php',
    'inc/Service_Loader.php',
    'inc/Core/Plugin.php'
);

foreach ($required_files as $file) {
    if (!file_exists(KSEO_PLUGIN_DIR . $file)) {
        kseo_handle_error('Required file missing: ' . $file);
        return;
    }
}

// Check for memory limit issues
$memory_limit = ini_get('memory_limit');
$memory_limit_bytes = wp_convert_hr_to_bytes($memory_limit);
if ($memory_limit_bytes < 256 * 1024 * 1024) { // Less than 256MB
    kseo_handle_error('Low memory limit detected: ' . $memory_limit . '. Recommended: 256M or higher.', 'warning');
}

// Safe autoloader with error handling
try {
    require_once KSEO_PLUGIN_DIR . 'autoload.php';
} catch (\Exception $e) {
    kseo_handle_error('Failed to load autoloader: ' . $e->getMessage());
    return;
}

// Initialize the plugin with comprehensive error handling
function kseo_init() {
    try {
        // Check if another SEO plugin is active to prevent conflicts
        $active_plugins = get_option('active_plugins', array());
        $conflicting_plugins = array();
        
        foreach ($active_plugins as $plugin) {
            if (strpos($plugin, 'yoast') !== false || 
                strpos($plugin, 'rankmath') !== false || 
                strpos($plugin, 'all-in-one-seo') !== false ||
                strpos($plugin, 'ke-lubricants-seo-booster') !== false) {
                $conflicting_plugins[] = $plugin;
            }
        }
        
        if (!empty($conflicting_plugins)) {
            kseo_handle_error('Conflicting SEO plugins detected. Please deactivate other SEO plugins before using KE SEO Booster Pro.', 'warning');
        }
        
        // Initialize the main plugin class
        new KSEO\SEO_Booster\Core\Plugin();
        
        // Log successful initialization
        error_log('KE SEO Booster Pro initialized successfully');
        
    } catch (\Exception $e) {
        kseo_handle_error('Initialization failed: ' . $e->getMessage());
        
        // Attempt to deactivate the plugin to prevent further crashes
        if (is_admin()) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p><strong>KE SEO Booster Pro has been automatically deactivated due to critical errors.</strong> Please check the error logs and contact support.</p></div>';
            });
            
            // Deactivate plugin after a short delay to allow the notice to display
            add_action('admin_init', function() {
                deactivate_plugins(plugin_basename(__FILE__));
            });
        }
    }
}

// Hook into WordPress with priority to ensure other plugins are loaded first
add_action('plugins_loaded', 'kseo_init', 20);

// Enhanced activation hook with better error handling
register_activation_hook(__FILE__, 'kseo_activation');
function kseo_activation() {
    try {
        // Check WordPress version
        if (version_compare(get_bloginfo('version'), KSEO_MIN_WP_VERSION, '<')) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(
                sprintf(
                    __('KE SEO Booster Pro requires WordPress %s or higher.', 'kseo-seo-booster'),
                    KSEO_MIN_WP_VERSION
                )
            );
        }

        // Check PHP version
        if (version_compare(PHP_VERSION, KSEO_MIN_PHP_VERSION, '<')) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(
                sprintf(
                    __('KE SEO Booster Pro requires PHP %s or higher.', 'kseo-seo-booster'),
                    KSEO_MIN_PHP_VERSION
                )
            );
        }

        // Check for conflicting plugins
        $active_plugins = get_option('active_plugins', array());
        foreach ($active_plugins as $plugin) {
            if (strpos($plugin, 'ke-lubricants-seo-booster') !== false) {
                deactivate_plugins(plugin_basename(__FILE__));
                wp_die(
                    __('KE SEO Booster Pro cannot be activated while KE Lubricants SEO Booster is active. Please deactivate the older plugin first.', 'kseo-seo-booster')
                );
            }
        }

        // Set default options with error handling
        $default_options = array(
            'kseo_modules' => array(
                'meta_box' => true,
                'meta_output' => true,
                'social_tags' => true,
                'schema' => true,
                'sitemap' => true,
                'robots' => true,
                'keyword_suggest' => false,
                'ai_generator' => false,
                'bulk_audit' => false,
                'internal_link' => false,
                'api' => true
            ),
            'kseo_post_types' => array('post', 'page'),
            'kseo_auto_generate' => true,
            'kseo_enable_schema' => true,
            'kseo_enable_og_tags' => true,
            'kseo_version' => KSEO_VERSION,
            'kseo_onboarding_completed' => false
        );

        foreach ($default_options as $option => $value) {
            if (get_option($option) === false) {
                add_option($option, $value);
            }
        }

        // Run installer to create/update tables
        if (class_exists('KSEO\\SEO_Booster\\Core\\Installer')) {
            KSEO\SEO_Booster\Core\Installer::install();
        } else {
            // Attempt to include installer if not loaded
            $installer_path = KSEO_PLUGIN_DIR . 'inc/Core/Installer.php';
            if (file_exists($installer_path)) {
                require_once $installer_path;
                if (class_exists('KSEO\\SEO_Booster\\Core\\Installer')) {
                    KSEO\SEO_Booster\Core\Installer::install();
                }
            }
        }

        // Flush rewrite rules
        flush_rewrite_rules();

        // Log successful activation
        error_log('KE SEO Booster Pro activated successfully');
        
    } catch (\Exception $e) {
        error_log('KE SEO Booster Pro activation failed: ' . $e->getMessage());
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die('KE SEO Booster Pro activation failed: ' . $e->getMessage());
    }
}

// Enhanced deactivation hook
register_deactivation_hook(__FILE__, 'kseo_deactivation');
function kseo_deactivation() {
    try {
        // Clear scheduled hooks
        wp_clear_scheduled_hook('kseo_daily_analysis');
        wp_clear_scheduled_hook('kseo_sitemap_regeneration');
        
        // Clear transients
        global $wpdb;
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '_transient_kseo_%'));
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '_transient_timeout_kseo_%'));
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Log deactivation
        error_log('KE SEO Booster Pro deactivated');
        
    } catch (\Exception $e) {
        error_log('KE SEO Booster Pro deactivation error: ' . $e->getMessage());
    }
} 
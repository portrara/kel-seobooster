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

// Check WordPress and PHP version compatibility
if (version_compare(get_bloginfo('version'), KSEO_MIN_WP_VERSION, '<')) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>KE SEO Booster Pro requires WordPress ' . KSEO_MIN_WP_VERSION . ' or higher.</p></div>';
    });
    return;
}

if (version_compare(PHP_VERSION, KSEO_MIN_PHP_VERSION, '<')) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>KE SEO Booster Pro requires PHP ' . KSEO_MIN_PHP_VERSION . ' or higher.</p></div>';
    });
    return;
}

// Autoloader
require_once KSEO_PLUGIN_DIR . 'autoload.php';

// Initialize the plugin
function kseo_init() {
    try {
        // Initialize the main plugin class
        new KSEO\SEO_Booster\Core\Plugin();
    } catch (Exception $e) {
        error_log('KE SEO Booster Pro initialization failed: ' . $e->getMessage());
        add_action('admin_notices', function() use ($e) {
            echo '<div class="notice notice-error"><p>KE SEO Booster Pro failed to initialize: ' . esc_html($e->getMessage()) . '</p></div>';
        });
    }
}

// Hook into WordPress
add_action('plugins_loaded', 'kseo_init');

// Activation hook
register_activation_hook(__FILE__, 'kseo_activation');
function kseo_activation() {
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

    // Set default options
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
            'internal_link' => false
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

    // Flush rewrite rules
    flush_rewrite_rules();

    // Log activation
    error_log('KE SEO Booster Pro activated successfully');
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'kseo_deactivation');
function kseo_deactivation() {
    // Clear scheduled hooks
    wp_clear_scheduled_hook('kseo_daily_analysis');
    wp_clear_scheduled_hook('kseo_sitemap_regeneration');
    
    // Clear transients
    global $wpdb;
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_kseo_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_kseo_%'");
    
    // Flush rewrite rules
    flush_rewrite_rules();
    
    // Log deactivation
    error_log('KE SEO Booster Pro deactivated');
} 
<?php
/**
 * Uninstall script for KE Lubricants SEO Booster
 * 
 * This script runs when the plugin is deleted from WordPress.
 * It removes all plugin data, options, and meta data.
 * 
 * @package KE_Lubricants_SEO_Booster
 * @since 2.0.0
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clean up all plugin data
 * 
 * This function removes all plugin-related data from the database
 * and cleans up any files or directories created by the plugin.
 */
function keseo_cleanup_plugin_data() {
    global $wpdb;
    
    try {
        // Log uninstall process
        error_log('KE Lubricants SEO Booster: Starting plugin uninstall cleanup');
        
        // Remove all plugin options
        $plugin_options = array(
            'keseo_openai_api_key',
            'keseo_post_types',
            'keseo_auto_generate',
            'keseo_enable_schema',
            'keseo_enable_og_tags',
            'keseo_onboarding_completed',
            'keseo_version',
            'keseo_google_ads_credentials'
        );
        
        foreach ($plugin_options as $option) {
            delete_option($option);
        }
        
        // Remove all transients
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_keseo_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_keseo_%'");
        
        // Remove all post meta
        $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_ke_seo_%'");
        
        // Remove any scheduled hooks
        wp_clear_scheduled_hook('keseo_daily_analysis');
        
        // Clear object cache if available
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
        
        // Remove any custom database tables (if any were created)
        $custom_tables = array(
            $wpdb->prefix . 'keseo_logs',
            $wpdb->prefix . 'keseo_cache'
        );
        
        foreach ($custom_tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$table}");
        }
        
        // Remove any uploaded files or directories
        $upload_dir = wp_upload_dir();
        $plugin_upload_dir = $upload_dir['basedir'] . '/ke-seo-booster';
        
        if (is_dir($plugin_upload_dir)) {
            keseo_remove_directory($plugin_upload_dir);
        }
        
        // Log successful cleanup
        error_log('KE Lubricants SEO Booster: Plugin uninstall cleanup completed successfully');
        
    } catch (Exception $e) {
        error_log('KE Lubricants SEO Booster: Plugin uninstall cleanup failed: ' . $e->getMessage());
    }
}

/**
 * Recursively remove a directory and its contents
 * 
 * @param string $dir The directory to remove
 * @return bool Success status
 */
function keseo_remove_directory($dir) {
    try {
        if (!is_dir($dir)) {
            return false;
        }
        
        $files = array_diff(scandir($dir), array('.', '..'));
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            
            if (is_dir($path)) {
                keseo_remove_directory($path);
            } else {
                unlink($path);
            }
        }
        
        return rmdir($dir);
        
    } catch (Exception $e) {
        error_log('KE Lubricants SEO Booster: Failed to remove directory: ' . $e->getMessage());
        return false;
    }
}

/**
 * Check if there are any remaining plugin references
 * 
 * @return array Remaining references
 */
function keseo_check_remaining_references() {
    global $wpdb;
    
    $remaining = array();
    
    try {
        // Check for remaining options
        $options = $wpdb->get_results("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'keseo_%'");
        if (!empty($options)) {
            $remaining['options'] = array_column($options, 'option_name');
        }
        
        // Check for remaining post meta
        $postmeta = $wpdb->get_results("SELECT meta_key FROM {$wpdb->postmeta} WHERE meta_key LIKE '_ke_seo_%'");
        if (!empty($postmeta)) {
            $remaining['postmeta'] = array_column($postmeta, 'meta_key');
        }
        
        // Check for remaining transients
        $transients = $wpdb->get_results("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '_transient_keseo_%'");
        if (!empty($transients)) {
            $remaining['transients'] = array_column($transients, 'option_name');
        }
        
    } catch (Exception $e) {
        error_log('KE Lubricants SEO Booster: Failed to check remaining references: ' . $e->getMessage());
    }
    
    return $remaining;
}

// Run the cleanup
keseo_cleanup_plugin_data();

// Check for any remaining references
$remaining = keseo_check_remaining_references();

if (!empty($remaining)) {
    error_log('KE Lubricants SEO Booster: Some plugin data could not be removed: ' . print_r($remaining, true));
}

// Final cleanup message
error_log('KE Lubricants SEO Booster: Plugin uninstall process completed'); 
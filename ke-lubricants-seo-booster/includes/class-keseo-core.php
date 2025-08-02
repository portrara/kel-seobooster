<?php
/**
 * Core functionality for KE Lubricants SEO Booster
 * 
 * Handles basic plugin functionality, database operations, and utility functions.
 * 
 * @package KE_Lubricants_SEO_Booster
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Core functionality class
 * 
 * CRASH PREVENTION: All methods include comprehensive error handling
 * SECURITY: All inputs are sanitized and outputs are escaped
 * LOGGING: All operations are logged for debugging
 */
class KESEO_Core {

    /**
     * Initialize the core functionality
     */
    public function __construct() {
        try {
            // Register settings
            add_action('admin_init', array($this, 'register_settings'));
            
            // Log successful initialization
            error_log('KE Lubricants SEO Booster: Core initialized successfully');
            
        } catch (Exception $e) {
            error_log('KE Lubricants SEO Booster: Core initialization failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Register plugin settings with security validation
     * 
     * SECURITY: All settings are properly registered with sanitization callbacks
     */
    public function register_settings() {
        try {
            // Register settings with sanitization
            register_setting('ke_seo_options', 'keseo_openai_api_key', array(
                'sanitize_callback' => array($this, 'sanitize_api_key')
            ));
            
            register_setting('ke_seo_options', 'keseo_post_types', array(
                'sanitize_callback' => array($this, 'sanitize_post_types')
            ));
            
            register_setting('ke_seo_options', 'keseo_auto_generate');
            register_setting('ke_seo_options', 'keseo_enable_schema');
            register_setting('ke_seo_options', 'keseo_enable_og_tags');
            register_setting('ke_seo_options', 'keseo_onboarding_completed');
            
        } catch (Exception $e) {
            error_log('KE Lubricants SEO Booster: Settings registration failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Sanitize API key input
     * 
     * SECURITY: Validates and sanitizes API key input
     * 
     * @param string $api_key The API key to sanitize
     * @return string The sanitized API key
     */
    public function sanitize_api_key($api_key) {
        try {
            $sanitized = sanitize_text_field($api_key);
            
            // Basic validation for OpenAI API key format
            if (!empty($sanitized) && !preg_match('/^sk-[a-zA-Z0-9]{32,}$/', $sanitized)) {
                add_settings_error(
                    'keseo_openai_api_key',
                    'invalid_api_key',
                    __('Invalid OpenAI API key format. Please check your key.', 'ke-seo-booster')
                );
                return '';
            }
            
            return $sanitized;
            
        } catch (Exception $e) {
            error_log('KE Lubricants SEO Booster: API key sanitization failed: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * Sanitize post types array
     * 
     * SECURITY: Validates and sanitizes post types array
     * 
     * @param array $post_types The post types array to sanitize
     * @return array The sanitized post types array
     */
    public function sanitize_post_types($post_types) {
        try {
            if (!is_array($post_types)) {
                return array('post', 'page');
            }
            
            $sanitized = array();
            $valid_post_types = get_post_types(array('public' => true), 'names');
            
            foreach ($post_types as $post_type) {
                $clean_type = sanitize_text_field($post_type);
                if (in_array($clean_type, $valid_post_types)) {
                    $sanitized[] = $clean_type;
                }
            }
            
            // Ensure at least one post type is selected
            if (empty($sanitized)) {
                $sanitized = array('post', 'page');
            }
            
            return $sanitized;
            
        } catch (Exception $e) {
            error_log('KE Lubricants SEO Booster: Post types sanitization failed: ' . $e->getMessage());
            return array('post', 'page');
        }
    }

    /**
     * Get bulk preview data
     * 
     * CRASH PREVENTION: Wrapped in try-catch with comprehensive error handling
     * 
     * @param array $post_types Array of post types to check
     * @return array Preview data
     */
    public function get_bulk_preview($post_types) {
        try {
            // Validate and sanitize post types
            $valid_post_types = $this->sanitize_post_types($post_types);
            
            // Get posts for preview
            $posts = get_posts(array(
                'post_type' => $valid_post_types,
                'post_status' => 'publish',
                'posts_per_page' => 10,
                'fields' => 'ids'
            ));
            
            // Get statistics
            $stats = $this->get_bulk_stats($valid_post_types);
            
            return array(
                'posts' => $posts,
                'total' => count($posts),
                'stats' => $stats
            );
            
        } catch (Exception $e) {
            error_log('KE Lubricants SEO Booster: Bulk preview failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get bulk operation statistics
     * 
     * @param array $post_types Array of post types
     * @return array Statistics data
     */
    private function get_bulk_stats($post_types) {
        try {
            global $wpdb;
            
            $stats = array(
                'total_posts' => 0,
                'optimized_posts' => 0,
                'posts_with_title' => 0,
                'posts_with_description' => 0,
                'posts_with_keywords' => 0
            );
            
            // Get total posts
            $stats['total_posts'] = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type IN (" . implode(',', array_fill(0, count($post_types), '%s')) . ") AND post_status = 'publish'",
                $post_types
            ));
            
            // Get posts with SEO title
            $stats['posts_with_title'] = $wpdb->get_var(
                "SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p 
                INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
                WHERE pm.meta_key = '_ke_seo_title' AND pm.meta_value != '' 
                AND p.post_type IN (" . implode(',', array_fill(0, count($post_types), '%s')) . ") 
                AND p.post_status = 'publish'"
            );
            
            // Get posts with SEO description
            $stats['posts_with_description'] = $wpdb->get_var(
                "SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p 
                INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
                WHERE pm.meta_key = '_ke_seo_description' AND pm.meta_value != '' 
                AND p.post_type IN (" . implode(',', array_fill(0, count($post_types), '%s')) . ") 
                AND p.post_status = 'publish'"
            );
            
            // Get posts with SEO keywords
            $stats['posts_with_keywords'] = $wpdb->get_var(
                "SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p 
                INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
                WHERE pm.meta_key = '_ke_seo_keywords' AND pm.meta_value != '' 
                AND p.post_type IN (" . implode(',', array_fill(0, count($post_types), '%s')) . ") 
                AND p.post_status = 'publish'"
            );
            
            // Calculate optimized posts (posts with at least title and description)
            $stats['optimized_posts'] = $wpdb->get_var(
                "SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p 
                INNER JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id 
                INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id 
                WHERE pm1.meta_key = '_ke_seo_title' AND pm1.meta_value != '' 
                AND pm2.meta_key = '_ke_seo_description' AND pm2.meta_value != '' 
                AND p.post_type IN (" . implode(',', array_fill(0, count($post_types), '%s')) . ") 
                AND p.post_status = 'publish'"
            );
            
            return $stats;
            
        } catch (Exception $e) {
            error_log('KE Lubricants SEO Booster: Stats calculation failed: ' . $e->getMessage());
            return array(
                'total_posts' => 0,
                'optimized_posts' => 0,
                'posts_with_title' => 0,
                'posts_with_description' => 0,
                'posts_with_keywords' => 0
            );
        }
    }

    /**
     * Get optimized posts count
     * 
     * @return int Number of optimized posts
     */
    public function get_optimized_posts_count() {
        try {
            global $wpdb;
            $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_ke_seo_title' AND meta_value != ''");
            return $count ? $count : 0;
        } catch (Exception $e) {
            error_log('KE Lubricants SEO Booster: Optimized posts count failed: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get total keywords count
     * 
     * @return int Number of posts with keywords
     */
    public function get_total_keywords_count() {
        try {
            global $wpdb;
            $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_ke_seo_keywords' AND meta_value != ''");
            return $count ? $count : 0;
        } catch (Exception $e) {
            error_log('KE Lubricants SEO Booster: Keywords count failed: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Clear plugin cache
     * 
     * @return bool Success status
     */
    public function clear_cache() {
        try {
            global $wpdb;
            
            // Clear transients
            $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_keseo_%'");
            $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_keseo_%'");
            
            // Clear object cache if available
            if (function_exists('wp_cache_flush')) {
                wp_cache_flush();
            }
            
            error_log('KE Lubricants SEO Booster: Cache cleared successfully');
            return true;
            
        } catch (Exception $e) {
            error_log('KE Lubricants SEO Booster: Cache clear failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Validate plugin installation
     * 
     * @return array Validation results
     */
    public function validate_installation() {
        $results = array(
            'php_version' => version_compare(PHP_VERSION, KESEO_MIN_PHP_VERSION, '>='),
            'wp_version' => version_compare(get_bloginfo('version'), KESEO_MIN_WP_VERSION, '>='),
            'memory_limit' => $this->check_memory_limit(),
            'file_permissions' => $this->check_file_permissions(),
            'database_connection' => $this->check_database_connection()
        );
        
        return $results;
    }

    /**
     * Check memory limit
     * 
     * @return bool Memory limit is sufficient
     */
    private function check_memory_limit() {
        try {
            $memory_limit = ini_get('memory_limit');
            $memory_limit_bytes = $this->convert_memory_limit($memory_limit);
            return $memory_limit_bytes >= 128 * 1024 * 1024; // 128MB
        } catch (Exception $e) {
            error_log('KE Lubricants SEO Booster: Memory check failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check file permissions
     * 
     * @return bool File permissions are correct
     */
    private function check_file_permissions() {
        try {
            $plugin_dir = KESEO_PLUGIN_PATH;
            return is_readable($plugin_dir) && is_writable($plugin_dir);
        } catch (Exception $e) {
            error_log('KE Lubricants SEO Booster: File permissions check failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check database connection
     * 
     * @return bool Database connection is working
     */
    private function check_database_connection() {
        try {
            global $wpdb;
            $result = $wpdb->get_var("SELECT 1");
            return $result === '1';
        } catch (Exception $e) {
            error_log('KE Lubricants SEO Booster: Database check failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Convert memory limit string to bytes
     * 
     * @param string $memory_limit Memory limit string
     * @return int Memory limit in bytes
     */
    private function convert_memory_limit($memory_limit) {
        $unit = strtolower(substr($memory_limit, -1));
        $value = (int) $memory_limit;
        
        switch ($unit) {
            case 'k':
                return $value * 1024;
            case 'm':
                return $value * 1024 * 1024;
            case 'g':
                return $value * 1024 * 1024 * 1024;
            default:
                return $value;
        }
    }
} 
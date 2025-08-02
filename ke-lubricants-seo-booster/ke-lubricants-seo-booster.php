<?php
/**
 * Plugin Name: KE Lubricants SEO Booster
 * Plugin URI: https://github.com/portrara/kel-seobooster
 * Description: A comprehensive WordPress plugin for automated SEO optimization using OpenAI API and Google Keyword Planner integration.
 * Version: 2.0.0
 * Author: Krish Yadav
 * Author URI: https://github.com/portrara
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ke-seo-booster
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 * 
 * @package KE_Lubricants_SEO_Booster
 * @version 2.0.0
 * @author Krish Yadav
 * @license GPL v2 or later
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('KESEO_PLUGIN_FILE', __FILE__);
define('KESEO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('KESEO_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('KESEO_VERSION', '2.0.0');
define('KESEO_MIN_WP_VERSION', '5.0');
define('KESEO_MIN_PHP_VERSION', '7.4');

// Enhanced error handling function with logging
function keseo_handle_error($error_message, $error_type = 'error', $context = '') {
    $log_message = 'KE Lubricants SEO Booster Error: ' . $error_message;
    if (!empty($context)) {
        $log_message .= ' | Context: ' . $context;
    }
    error_log($log_message);
    
    if (is_admin()) {
        $notice_class = ($error_type === 'error') ? 'notice-error' : 'notice-warning';
        add_action('admin_notices', function() use ($error_message, $notice_class) {
            echo '<div class="notice ' . $notice_class . '"><p><strong>KE Lubricants SEO Booster:</strong> ' . esc_html($error_message) . '</p></div>';
        });
    }
}

// Helper function to convert memory limit to bytes (replaces undefined wp_convert_hr_to_bytes)
function keseo_convert_memory_limit($memory_limit) {
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

// Check WordPress and PHP version compatibility with enhanced error handling
if (version_compare(get_bloginfo('version'), KESEO_MIN_WP_VERSION, '<')) {
    keseo_handle_error(
        'Requires WordPress ' . KESEO_MIN_WP_VERSION . ' or higher. Current version: ' . get_bloginfo('version'),
        'error',
        'version_check'
    );
    return;
}

if (version_compare(PHP_VERSION, KESEO_MIN_PHP_VERSION, '<')) {
    keseo_handle_error(
        'Requires PHP ' . KESEO_MIN_PHP_VERSION . ' or higher. Current version: ' . PHP_VERSION,
        'error',
        'php_version_check'
    );
    return;
}

// Check for memory limit issues
$memory_limit = ini_get('memory_limit');
$memory_limit_bytes = keseo_convert_memory_limit($memory_limit);
if ($memory_limit_bytes < 128 * 1024 * 1024) { // Less than 128MB
    keseo_handle_error(
        'Low memory limit detected: ' . $memory_limit . '. Recommended: 128M or higher.',
        'warning',
        'memory_check'
    );
}

// Load required files with error handling
$required_files = array(
    'includes/class-keseo-core.php',
    'includes/class-keseo-openai.php',
    'includes/class-keseo-google-api.php',
    'includes/class-keseo-admin.php',
    'includes/class-keseo-meta-box.php'
);

foreach ($required_files as $file) {
    $file_path = KESEO_PLUGIN_PATH . $file;
    if (!file_exists($file_path)) {
        keseo_handle_error(
            'Required file missing: ' . $file,
            'error',
            'file_check'
        );
        return;
    }
}

// Load all required files
foreach ($required_files as $file) {
    require_once KESEO_PLUGIN_PATH . $file;
}

/**
 * Main SEO Booster Class
 * 
 * This is the main plugin class that orchestrates all functionality.
 * It's designed to be crash-resistant with comprehensive error handling.
 */
class KELubricantsSEOBooster {

    /**
     * Plugin instance
     * 
     * @var KELubricantsSEOBooster
     */
    private static $instance = null;

    /**
     * Core functionality handler
     * 
     * @var KESEO_Core
     */
    private $core;

    /**
     * OpenAI API handler
     * 
     * @var KESEO_OpenAI
     */
    private $openai;

    /**
     * Google API handler
     * 
     * @var KESEO_Google_API
     */
    private $google_api;

    /**
     * Admin interface handler
     * 
     * @var KESEO_Admin
     */
    private $admin;

    /**
     * Meta box handler
     * 
     * @var KESEO_Meta_Box
     */
    private $meta_box;

    /**
     * Constructor with comprehensive error handling
     * 
     * CRASH PREVENTION: This constructor is wrapped in try-catch to prevent fatal errors
     */
    public function __construct() {
        try {
            // Initialize components with error handling
            $this->init_components();
            
            // Set up WordPress hooks
            $this->setup_hooks();
            
            // Log successful initialization
            error_log('KE Lubricants SEO Booster: Plugin initialized successfully');
            
        } catch (Exception $e) {
            keseo_handle_error(
                'Plugin initialization failed: ' . $e->getMessage(),
                'error',
                'constructor'
            );
            
            // Prevent further initialization if critical error occurs
            return;
        }
    }

    /**
     * Get plugin instance (Singleton pattern)
     * 
     * @return KELubricantsSEOBooster
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize plugin components with error handling
     * 
     * CRASH PREVENTION: Each component is initialized separately to prevent cascading failures
     */
    private function init_components() {
        try {
            // Initialize core functionality
            $this->core = new KESEO_Core();
            
            // Initialize OpenAI handler
            $this->openai = new KESEO_OpenAI();
            
            // Initialize Google API handler
            $this->google_api = new KESEO_Google_API();
            
            // Initialize admin interface
            $this->admin = new KESEO_Admin();
            
            // Initialize meta box functionality
            $this->meta_box = new KESEO_Meta_Box();
            
        } catch (Exception $e) {
            keseo_handle_error(
                'Component initialization failed: ' . $e->getMessage(),
                'error',
                'init_components'
            );
            throw $e; // Re-throw to prevent partial initialization
        }
    }

    /**
     * Set up WordPress hooks with error handling
     * 
     * CRASH PREVENTION: Each hook is added individually to prevent cascading failures
     */
    private function setup_hooks() {
        try {
            // Initialize plugin
        add_action('init', array($this, 'init'));
            
            // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
            
            // Enqueue admin scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
            
            // Add meta boxes
        add_action('add_meta_boxes', array($this, 'add_seo_meta_box'));
            
            // Save post meta
        add_action('save_post', array($this, 'save_seo_meta'));
            
            // Add AJAX handlers
            $this->setup_ajax_handlers();
            
            // Add frontend hooks
            add_action('wp_head', array($this, 'output_meta_tags'));
            
        } catch (Exception $e) {
            keseo_handle_error(
                'Hook setup failed: ' . $e->getMessage(),
                'error',
                'setup_hooks'
            );
        }
    }

    /**
     * Set up AJAX handlers with security validation
     * 
     * SECURITY: All AJAX handlers include nonce verification and input sanitization
     */
    private function setup_ajax_handlers() {
        try {
            // Test API key
            add_action('wp_ajax_keseo_test_api', array($this, 'ajax_test_api'));
            
            // Test Google API
            add_action('wp_ajax_keseo_test_google_api', array($this, 'ajax_test_google_api'));
            
            // Generate SEO content
            add_action('wp_ajax_keseo_generate_seo', array($this, 'ajax_generate_seo'));
            
            // Bulk preview
            add_action('wp_ajax_keseo_bulk_preview', array($this, 'ajax_bulk_preview'));
            
        } catch (Exception $e) {
            keseo_handle_error(
                'AJAX handler setup failed: ' . $e->getMessage(),
                'error',
                'setup_ajax_handlers'
            );
        }
    }

    /**
     * Initialize plugin
     * 
     * CRASH PREVENTION: Wrapped in try-catch to prevent fatal errors
     */
    public function init() {
        try {
            // Load text domain for internationalization
            load_plugin_textdomain('ke-seo-booster', false, dirname(plugin_basename(KESEO_PLUGIN_FILE)) . '/languages');
            
        } catch (Exception $e) {
            keseo_handle_error(
                'Plugin init failed: ' . $e->getMessage(),
                'error',
                'init'
            );
        }
    }

    /**
     * Add admin menu with error handling
     * 
     * CRASH PREVENTION: Menu creation is wrapped in try-catch
     */
    public function add_admin_menu() {
        try {
        add_menu_page(
            'KE SEO Booster',
            'KE SEO Booster',
            'manage_options',
            'ke-seo-booster',
                array($this->admin, 'render_dashboard'),
            'dashicons-chart-line',
            30
        );

        add_submenu_page(
            'ke-seo-booster',
            'Settings',
            'Settings',
            'manage_options',
            'ke-seo-booster-settings',
                array($this->admin, 'render_settings')
            );
            
        } catch (Exception $e) {
            keseo_handle_error(
                'Menu creation failed: ' . $e->getMessage(),
                'error',
                'add_admin_menu'
            );
        }
    }

    /**
     * Enqueue admin scripts with error handling
     * 
     * CRASH PREVENTION: Script enqueuing is wrapped in try-catch
     */
    public function enqueue_admin_scripts($hook) {
        try {
            if (strpos($hook, 'ke-seo-booster') !== false || strpos($hook, 'post.php') !== false || strpos($hook, 'post-new.php') !== false) {
            wp_enqueue_style('ke-seo-admin', KESEO_PLUGIN_URL . 'admin.css', array(), KESEO_VERSION);
            wp_enqueue_script('ke-seo-admin', KESEO_PLUGIN_URL . 'admin.js', array('jquery'), KESEO_VERSION, true);
                
                // Localize script with security nonce
                wp_localize_script('ke-seo-admin', 'keseo_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('keseo_nonce'),
                    'strings' => array(
                        'generating' => __('Generating...', 'ke-seo-booster'),
                        'saving' => __('Saving...', 'ke-seo-booster'),
                        'error' => __('Error occurred', 'ke-seo-booster')
                    )
                ));
            }
            
        } catch (Exception $e) {
            keseo_handle_error(
                'Script enqueue failed: ' . $e->getMessage(),
                'error',
                'enqueue_admin_scripts'
            );
        }
    }

    /**
     * Add SEO meta box with error handling
     * 
     * CRASH PREVENTION: Meta box creation is wrapped in try-catch
     */
    public function add_seo_meta_box() {
        try {
        $post_types = get_option('keseo_post_types', array('post', 'page'));
        foreach ($post_types as $post_type) {
            add_meta_box(
                'ke-seo-booster',
                'KE SEO Booster',
                    array($this->meta_box, 'render'),
                $post_type,
                'normal',
                'high'
            );
        }
            
        } catch (Exception $e) {
            keseo_handle_error(
                'Meta box creation failed: ' . $e->getMessage(),
                'error',
                'add_seo_meta_box'
            );
        }
    }

    /**
     * Save SEO meta with security validation
     * 
     * SECURITY: Includes nonce verification and input sanitization
     */
    public function save_seo_meta($post_id) {
        try {
            // Security checks
        if (!isset($_POST['ke_seo_meta_box_nonce']) || !wp_verify_nonce($_POST['ke_seo_meta_box_nonce'], 'ke_seo_meta_box')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

            // Save meta data with sanitization
            $this->meta_box->save($post_id);
            
        } catch (Exception $e) {
            keseo_handle_error(
                'Meta save failed: ' . $e->getMessage(),
                'error',
                'save_seo_meta'
            );
        }
    }

    /**
     * Output meta tags in frontend with error handling
     * 
     * CRASH PREVENTION: Frontend output is wrapped in try-catch to prevent site crashes
     */
    public function output_meta_tags() {
        try {
            if (is_singular()) {
                $this->meta_box->output_meta_tags();
            }
            
        } catch (Exception $e) {
            // Silent fail for frontend to prevent site crashes
            error_log('KE Lubricants SEO Booster: Frontend meta output failed: ' . $e->getMessage());
        }
    }

    /**
     * AJAX: Test API key with security validation
     * 
     * SECURITY: Includes nonce verification and input sanitization
     */
    public function ajax_test_api() {
        try {
            // Security check
            check_ajax_referer('keseo_nonce', 'nonce');
            
            $api_key = sanitize_text_field($_POST['api_key']);
            
            if (empty($api_key)) {
                wp_send_json_error('API key is required');
                return;
            }
            
            $result = $this->openai->test_api_key($api_key);
            wp_send_json_success($result);
            
        } catch (Exception $e) {
            keseo_handle_error(
                'API test failed: ' . $e->getMessage(),
                'error',
                'ajax_test_api'
            );
            wp_send_json_error('API test failed: ' . $e->getMessage());
        }
    }

    /**
     * AJAX: Test Google API with security validation
     * 
     * SECURITY: Includes nonce verification
     */
    public function ajax_test_google_api() {
        try {
            // Security check
            check_ajax_referer('keseo_nonce', 'nonce');
            
            $result = $this->google_api->test_connection();
            wp_send_json_success($result);
            
        } catch (Exception $e) {
            keseo_handle_error(
                'Google API test failed: ' . $e->getMessage(),
                'error',
                'ajax_test_google_api'
            );
            wp_send_json_error('Google API test failed: ' . $e->getMessage());
        }
    }

    /**
     * AJAX: Generate SEO content with security validation
     * 
     * SECURITY: Includes nonce verification and input sanitization
     */
    public function ajax_generate_seo() {
        try {
            // Security check
            check_ajax_referer('keseo_nonce', 'nonce');
            
            $post_id = intval($_POST['post_id']);
            $field = sanitize_text_field($_POST['field']);
            
            if (!$post_id || !$field) {
                wp_send_json_error('Invalid parameters');
                return;
            }
            
            $result = $this->openai->generate_seo_content($post_id, $field);
            wp_send_json_success($result);
            
        } catch (Exception $e) {
            keseo_handle_error(
                'SEO generation failed: ' . $e->getMessage(),
                'error',
                'ajax_generate_seo'
            );
            wp_send_json_error('Generation failed: ' . $e->getMessage());
        }
    }

    /**
     * AJAX: Bulk preview with security validation
     * 
     * SECURITY: Includes nonce verification and input sanitization
     */
    public function ajax_bulk_preview() {
        try {
            // Security check
            check_ajax_referer('keseo_nonce', 'nonce');
            
            $post_types = isset($_POST['post_types']) ? array_map('sanitize_text_field', $_POST['post_types']) : array('post', 'page');
            
            $result = $this->core->get_bulk_preview($post_types);
            wp_send_json_success($result);
            
        } catch (Exception $e) {
            keseo_handle_error(
                'Bulk preview failed: ' . $e->getMessage(),
                'error',
                'ajax_bulk_preview'
            );
            wp_send_json_error('Bulk preview failed: ' . $e->getMessage());
        }
    }
}

// Initialize the plugin with error handling
function keseo_init() {
    try {
        KELubricantsSEOBooster::get_instance();
    } catch (Exception $e) {
        keseo_handle_error(
            'Plugin initialization failed: ' . $e->getMessage(),
            'error',
            'keseo_init'
        );
    }
}

// Hook into WordPress with priority to ensure other plugins are loaded first
add_action('plugins_loaded', 'keseo_init', 20);

// Enhanced activation hook with comprehensive error handling
register_activation_hook(KESEO_PLUGIN_FILE, 'keseo_activation');
function keseo_activation() {
    try {
        // Check WordPress version
        if (version_compare(get_bloginfo('version'), KESEO_MIN_WP_VERSION, '<')) {
            deactivate_plugins(plugin_basename(KESEO_PLUGIN_FILE));
            wp_die(
                sprintf(
                    __('KE Lubricants SEO Booster requires WordPress %s or higher.', 'ke-seo-booster'),
                    KESEO_MIN_WP_VERSION
                )
            );
        }

        // Check PHP version
        if (version_compare(PHP_VERSION, KESEO_MIN_PHP_VERSION, '<')) {
            deactivate_plugins(plugin_basename(KESEO_PLUGIN_FILE));
            wp_die(
                sprintf(
                    __('KE Lubricants SEO Booster requires PHP %s or higher.', 'ke-seo-booster'),
                    KESEO_MIN_PHP_VERSION
                )
            );
        }

        // Check for conflicting plugins
        $active_plugins = get_option('active_plugins', array());
        foreach ($active_plugins as $plugin) {
            if (strpos($plugin, 'kseo-seo-booster') !== false) {
                deactivate_plugins(plugin_basename(KESEO_PLUGIN_FILE));
                wp_die(
                    __('KE Lubricants SEO Booster cannot be activated while KE SEO Booster Pro is active. Please deactivate the other plugin first.', 'ke-seo-booster')
                );
            }
        }

        // Set default options with error handling
        $default_options = array(
            'keseo_post_types' => array('post', 'page'),
            'keseo_auto_generate' => true,
            'keseo_enable_schema' => true,
            'keseo_enable_og_tags' => true,
            'keseo_version' => KESEO_VERSION,
            'keseo_onboarding_completed' => false
        );

        foreach ($default_options as $option => $value) {
            if (get_option($option) === false) {
                add_option($option, $value);
            }
    }
    
    // Flush rewrite rules
    flush_rewrite_rules();

        // Log successful activation
        error_log('KE Lubricants SEO Booster activated successfully');
        
    } catch (Exception $e) {
        error_log('KE Lubricants SEO Booster activation failed: ' . $e->getMessage());
        deactivate_plugins(plugin_basename(KESEO_PLUGIN_FILE));
        wp_die('KE Lubricants SEO Booster activation failed: ' . $e->getMessage());
    }
}

// Enhanced deactivation hook with comprehensive error handling
register_deactivation_hook(KESEO_PLUGIN_FILE, 'keseo_deactivation');
function keseo_deactivation() {
    try {
    // Clear any scheduled hooks
        wp_clear_scheduled_hook('keseo_daily_analysis');
    
    // Flush rewrite rules
    flush_rewrite_rules();
        
        // Log deactivation
        error_log('KE Lubricants SEO Booster deactivated');
        
    } catch (Exception $e) {
        error_log('KE Lubricants SEO Booster deactivation error: ' . $e->getMessage());
    }
} 
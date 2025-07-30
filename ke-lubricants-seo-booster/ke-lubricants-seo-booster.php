<?php
/**
 * Plugin Name: KE Lubricants SEO Booster
 * Plugin URI: https://github.com/portrara/kel-seobooster
 * Description: A comprehensive WordPress plugin for automated SEO optimization using OpenAI API and Google Keyword Planner integration.
 * Version: 1.1
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
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('KESEO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('KESEO_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('KESEO_VERSION', '1.1');
define('KESEO_MIN_WP_VERSION', '5.0');
define('KESEO_MIN_PHP_VERSION', '7.4');

// Check WordPress and PHP version compatibility
if (version_compare(get_bloginfo('version'), KESEO_MIN_WP_VERSION, '<')) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>KE SEO Booster requires WordPress ' . KESEO_MIN_WP_VERSION . ' or higher.</p></div>';
    });
    return;
}

if (version_compare(PHP_VERSION, KESEO_MIN_PHP_VERSION, '<')) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>KE SEO Booster requires PHP ' . KESEO_MIN_PHP_VERSION . ' or higher.</p></div>';
    });
    return;
}

/**
 * Main SEO Booster Class
 */
class KELubricantsSEOBooster {

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('add_meta_boxes', array($this, 'add_seo_meta_box'));
        add_action('save_post', array($this, 'save_seo_meta'));
    }

    public function init() {
        // Initialize plugin
        load_plugin_textdomain('ke-seo-booster', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function add_admin_menu() {
        add_menu_page(
            'KE SEO Booster',
            'KE SEO Booster',
            'manage_options',
            'ke-seo-booster',
            array($this, 'admin_page'),
            'dashicons-chart-line',
            30
        );

        add_submenu_page(
            'ke-seo-booster',
            'Settings',
            'Settings',
            'manage_options',
            'ke-seo-booster-settings',
            array($this, 'settings_page')
        );
    }

    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'ke-seo-booster') !== false) {
            wp_enqueue_style('ke-seo-admin', KESEO_PLUGIN_URL . 'admin.css', array(), KESEO_VERSION);
            wp_enqueue_script('ke-seo-admin', KESEO_PLUGIN_URL . 'admin.js', array('jquery'), KESEO_VERSION, true);
            wp_localize_script('ke-seo-admin', 'ke_seo_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ke_seo_nonce')
            ));
        }
    }

    public function add_seo_meta_box() {
        $post_types = get_option('keseo_post_types', array('post', 'page'));
        foreach ($post_types as $post_type) {
            add_meta_box(
                'ke-seo-booster',
                'KE SEO Booster',
                array($this, 'seo_meta_box_callback'),
                $post_type,
                'normal',
                'high'
            );
        }
    }

    public function seo_meta_box_callback($post) {
        wp_nonce_field('ke_seo_meta_box', 'ke_seo_meta_box_nonce');
        
        $seo_title = get_post_meta($post->ID, '_ke_seo_title', true);
        $seo_description = get_post_meta($post->ID, '_ke_seo_description', true);
        $seo_keywords = get_post_meta($post->ID, '_ke_seo_keywords', true);
        
        ?>
        <div class="ke-seo-meta-box">
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="ke_seo_title">SEO Title</label>
                    </th>
                    <td>
                        <input type="text" id="ke_seo_title" name="ke_seo_title" value="<?php echo esc_attr($seo_title); ?>" class="regular-text" />
                        <p class="description">Enter the SEO title for this post.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="ke_seo_description">SEO Description</label>
                    </th>
                    <td>
                        <textarea id="ke_seo_description" name="ke_seo_description" rows="3" class="large-text"><?php echo esc_textarea($seo_description); ?></textarea>
                        <p class="description">Enter the SEO description for this post.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="ke_seo_keywords">SEO Keywords</label>
                    </th>
                    <td>
                        <input type="text" id="ke_seo_keywords" name="ke_seo_keywords" value="<?php echo esc_attr($seo_keywords); ?>" class="regular-text" />
                        <p class="description">Enter the focus keywords for this post.</p>
                    </td>
                </tr>
            </table>
            <p>
                <button type="button" class="button button-primary" id="ke_seo_generate">Generate SEO Content</button>
                <span class="spinner" style="float: none; margin-top: 0;"></span>
            </p>
        </div>
        <?php
    }

    public function save_seo_meta($post_id) {
        if (!isset($_POST['ke_seo_meta_box_nonce']) || !wp_verify_nonce($_POST['ke_seo_meta_box_nonce'], 'ke_seo_meta_box')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (isset($_POST['ke_seo_title'])) {
            update_post_meta($post_id, '_ke_seo_title', sanitize_text_field($_POST['ke_seo_title']));
        }

        if (isset($_POST['ke_seo_description'])) {
            update_post_meta($post_id, '_ke_seo_description', sanitize_textarea_field($_POST['ke_seo_description']));
        }

        if (isset($_POST['ke_seo_keywords'])) {
            update_post_meta($post_id, '_ke_seo_keywords', sanitize_text_field($_POST['ke_seo_keywords']));
        }
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>KE SEO Booster</h1>
            <div class="ke-seo-dashboard">
                <h2>Welcome to KE SEO Booster</h2>
                <p>This plugin helps you optimize your content for search engines using AI-powered SEO generation.</p>
                
                <div class="ke-seo-stats">
                    <h3>Quick Stats</h3>
                    <p>Posts optimized: <strong><?php echo $this->get_optimized_posts_count(); ?></strong></p>
                    <p>Total keywords: <strong><?php echo $this->get_total_keywords_count(); ?></strong></p>
                </div>

                <div class="ke-seo-actions">
                    <h3>Quick Actions</h3>
                    <a href="<?php echo admin_url('admin.php?page=ke-seo-booster-settings'); ?>" class="button button-primary">Configure Settings</a>
                    <a href="<?php echo admin_url('edit.php'); ?>" class="button">Manage Posts</a>
                </div>
            </div>
        </div>
        <?php
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>KE SEO Booster Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('ke_seo_options');
                do_settings_sections('ke_seo_options');
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="keseo_openai_api_key">OpenAI API Key</label>
                        </th>
                        <td>
                            <input type="password" id="keseo_openai_api_key" name="keseo_openai_api_key" value="<?php echo esc_attr(get_option('keseo_openai_api_key')); ?>" class="regular-text" />
                            <p class="description">Enter your OpenAI API key for AI-powered SEO generation.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="keseo_post_types">Post Types</label>
                        </th>
                        <td>
                            <?php
                            $post_types = get_post_types(array('public' => true), 'objects');
                            $selected_types = get_option('keseo_post_types', array('post', 'page'));
                            foreach ($post_types as $post_type) {
                                $checked = in_array($post_type->name, $selected_types) ? 'checked' : '';
                                echo '<label><input type="checkbox" name="keseo_post_types[]" value="' . esc_attr($post_type->name) . '" ' . $checked . ' /> ' . esc_html($post_type->label) . '</label><br>';
                            }
                            ?>
                            <p class="description">Select post types to enable SEO optimization.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    private function get_optimized_posts_count() {
        global $wpdb;
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_ke_seo_title' AND meta_value != ''");
        return $count ? $count : 0;
    }

    private function get_total_keywords_count() {
        global $wpdb;
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_ke_seo_keywords' AND meta_value != ''");
        return $count ? $count : 0;
    }
}

// Initialize the plugin
function ke_seo_init() {
    new KELubricantsSEOBooster();
}

add_action('plugins_loaded', 'ke_seo_init');

// Register settings
function ke_seo_register_settings() {
    register_setting('ke_seo_options', 'keseo_openai_api_key');
    register_setting('ke_seo_options', 'keseo_post_types');
}

add_action('admin_init', 'ke_seo_register_settings');

// Activation hook
register_activation_hook(__FILE__, 'ke_seo_activation');
function ke_seo_activation() {
    // Set default options
    if (get_option('keseo_post_types') === false) {
        add_option('keseo_post_types', array('post', 'page'));
    }
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'ke_seo_deactivation');
function ke_seo_deactivation() {
    // Clear any scheduled hooks
    wp_clear_scheduled_hook('ke_seo_daily_analysis');
    
    // Flush rewrite rules
    flush_rewrite_rules();
} 
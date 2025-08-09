<?php
/**
 * Main Plugin Class for KE SEO Booster Pro
 * 
 * @package KSEO\SEO_Booster\Core
 */

namespace KSEO\SEO_Booster\Core;

use KSEO\SEO_Booster\Service_Loader;

class Plugin {
    
    /**
     * Service loader instance
     * 
     * @var Service_Loader
     */
    private $service_loader;
    
    /**
     * Initialize the plugin
     */
    public function __construct() {
        $this->init();
    }
    
    /**
     * Initialize plugin components
     */
    private function init() {
        // Load text domain
        add_action('init', array($this, 'load_textdomain'));
        
        // Initialize service loader
        $this->service_loader = new Service_Loader();
        
        // Add admin hooks
        if (is_admin()) {
            $this->init_admin();
        }
        
        // Add frontend hooks
        $this->init_frontend();
        
        // Add AJAX handlers
        $this->init_ajax();
        
        // Add WP-CLI commands
        if (defined('WP_CLI') && WP_CLI) {
            $this->init_cli();
        }
    }
    
    /**
     * Load text domain for internationalization
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'kseo-seo-booster',
            false,
            dirname(KSEO_PLUGIN_BASENAME) . '/languages'
        );
    }
    
    /**
     * Initialize admin functionality
     */
    private function init_admin() {
        // Enqueue admin scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Add plugin action links
        add_filter('plugin_action_links_' . KSEO_PLUGIN_BASENAME, array($this, 'add_plugin_links'));
        
        // Add meta boxes
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        
        // Save post meta
        add_action('save_post', array($this, 'save_post_meta'));
        
        // Add admin notices
        add_action('admin_notices', array($this, 'admin_notices'));
    }
    
    /**
     * Initialize frontend functionality
     */
    private function init_frontend() {
        // Add meta tags to head
        add_action('wp_head', array($this, 'output_meta_tags'), 1);
        
        // Add schema markup
        add_action('wp_head', array($this, 'output_schema_markup'), 2);
        
        // Add Open Graph tags
        add_action('wp_head', array($this, 'output_og_tags'), 3);
    }
    
    /**
     * Initialize AJAX handlers
     */
    private function init_ajax() {
        // AJAX actions for meta generation
        add_action('wp_ajax_kseo_generate_meta', array($this, 'ajax_generate_meta'));
        add_action('wp_ajax_kseo_save_meta', array($this, 'ajax_save_meta'));
        
        // AJAX actions for keyword suggestions
        add_action('wp_ajax_kseo_get_keyword_suggestions', array($this, 'ajax_get_keyword_suggestions'));
        
        // AJAX actions for bulk operations
        add_action('wp_ajax_kseo_bulk_audit', array($this, 'ajax_bulk_audit'));
    }
    
    /**
     * Initialize WP-CLI commands
     */
    private function init_cli() {
        // Register WP-CLI commands
        \WP_CLI::add_command('kseo', 'KSEO\\SEO_Booster\\CLI\\Commands');
    }
    
    /**
     * Enqueue admin assets
     * 
     * @param string $hook
     */
    public function enqueue_admin_assets($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'kseo') !== false || strpos($hook, 'post.php') !== false || strpos($hook, 'post-new.php') !== false) {
            wp_enqueue_style(
                'kseo-admin',
                KSEO_PLUGIN_URL . 'assets/admin.css',
                array(),
                KSEO_VERSION
            );
            
            wp_enqueue_script(
                'kseo-admin',
                KSEO_PLUGIN_URL . 'assets/admin.js',
                array('jquery'),
                KSEO_VERSION,
                true
            );
            
            wp_localize_script('kseo-admin', 'kseo_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('kseo_nonce'),
                'strings' => array(
                    'generating' => __('Generating...', 'kseo-seo-booster'),
                    'saving' => __('Saving...', 'kseo-seo-booster'),
                    'error' => __('Error occurred', 'kseo-seo-booster')
                )
            ));
        }
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('KE SEO Booster Pro', 'kseo-seo-booster'),
            __('SEO Booster', 'kseo-seo-booster'),
            'manage_options',
            'kseo-dashboard',
            array($this, 'dashboard_page'),
            'dashicons-chart-line',
            30
        );
        
        add_submenu_page(
            'kseo-dashboard',
            __('Dashboard', 'kseo-seo-booster'),
            __('Dashboard', 'kseo-seo-booster'),
            'manage_options',
            'kseo-dashboard',
            array($this, 'dashboard_page')
        );
        
        add_submenu_page(
            'kseo-dashboard',
            __('Settings', 'kseo-seo-booster'),
            __('Settings', 'kseo-seo-booster'),
            'manage_options',
            'kseo-settings',
            array($this, 'settings_page')
        );
        
        add_submenu_page(
            'kseo-dashboard',
            __('Bulk Audit', 'kseo-seo-booster'),
            __('Bulk Audit', 'kseo-seo-booster'),
            'manage_options',
            'kseo-bulk-audit',
            array($this, 'bulk_audit_page')
        );
    }
    
    /**
     * Add plugin action links
     * 
     * @param array $links
     * @return array
     */
    public function add_plugin_links($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=kseo-settings') . '">' . __('Settings', 'kseo-seo-booster') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
    
    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        $post_types = get_option('kseo_post_types', array('post', 'page'));
        
        foreach ($post_types as $post_type) {
            add_meta_box(
                'kseo-meta-box',
                __('KE SEO Booster Pro', 'kseo-seo-booster'),
                array($this, 'meta_box_callback'),
                $post_type,
                'normal',
                'high'
            );
        }
    }
    
    /**
     * Meta box callback
     * 
     * @param WP_Post $post
     */
    public function meta_box_callback($post) {
        $meta_box = $this->service_loader->get_module('meta_box');
        if ($meta_box) {
            $meta_box->render($post);
        }
    }
    
    /**
     * Save post meta
     * 
     * @param int $post_id
     */
    public function save_post_meta($post_id) {
        $meta_box = $this->service_loader->get_module('meta_box');
        if ($meta_box) {
            $meta_box->save($post_id);
        }
    }
    
    /**
     * Output meta tags
     */
    public function output_meta_tags() {
        $meta_output = $this->service_loader->get_module('meta_output');
        if ($meta_output) {
            $meta_output->output();
        }
    }
    
    /**
     * Output schema markup
     */
    public function output_schema_markup() {
        $schema = $this->service_loader->get_module('schema');
        if ($schema) {
            $schema->output();
        }
    }
    
    /**
     * Output Open Graph tags
     */
    public function output_og_tags() {
        $social_tags = $this->service_loader->get_module('social_tags');
        if ($social_tags) {
            $social_tags->output();
        }
    }
    
    /**
     * Dashboard page
     */
    public function dashboard_page() {
        include KSEO_PLUGIN_DIR . 'inc/views/dashboard.php';
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        $settings = $this->service_loader->get_module('settings');
        if ($settings) {
            $settings->render();
        }
    }
    
    /**
     * Bulk audit page
     */
    public function bulk_audit_page() {
        $bulk_audit = $this->service_loader->get_module('bulk_audit');
        if ($bulk_audit) {
            $bulk_audit->render();
        }
    }
    
    /**
     * Admin notices
     */
    public function admin_notices() {
        // Show onboarding notice if not completed
        if (!get_option('kseo_onboarding_completed', false)) {
            echo '<div class="notice notice-info"><p>' . 
                 sprintf(
                     __('Welcome to KE SEO Booster Pro! <a href="%s">Complete the setup wizard</a> to get started.', 'kseo-seo-booster'),
                     admin_url('admin.php?page=kseo-settings&tab=onboarding')
                 ) . 
                 '</p></div>';
        }
    }
    
    /**
     * AJAX: Generate meta
     */
    public function ajax_generate_meta() {
        check_ajax_referer('kseo_nonce', 'nonce');
        if (!current_user_can('kseo_optimize_content') && !current_user_can('edit_posts')) {
            wp_send_json_error(__('Permission denied', 'kseo-seo-booster'));
        }
        
        $ai_generator = $this->service_loader->get_module('ai_generator');
        if ($ai_generator) {
            $result = $ai_generator->generate_meta($_POST);
            wp_send_json_success($result);
        } else {
            wp_send_json_error(__('AI Generator module not available', 'kseo-seo-booster'));
        }
    }
    
    /**
     * AJAX: Save meta
     */
    public function ajax_save_meta() {
        check_ajax_referer('kseo_nonce', 'nonce');
        if (!current_user_can('kseo_optimize_content') && !current_user_can('edit_posts')) {
            wp_send_json_error(__('Permission denied', 'kseo-seo-booster'));
        }
        
        $meta_box = $this->service_loader->get_module('meta_box');
        if ($meta_box) {
            $result = $meta_box->save_ajax($_POST);
            wp_send_json_success($result);
        } else {
            wp_send_json_error(__('Meta Box module not available', 'kseo-seo-booster'));
        }
    }
    
    /**
     * AJAX: Get keyword suggestions
     */
    public function ajax_get_keyword_suggestions() {
        check_ajax_referer('kseo_nonce', 'nonce');
        if (!current_user_can('kseo_manage_settings') && !current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied', 'kseo-seo-booster'));
        }
        
        $keyword_suggest = $this->service_loader->get_module('keyword_suggest');
        if ($keyword_suggest) {
            $result = $keyword_suggest->get_suggestions($_POST);
            wp_send_json_success($result);
        } else {
            wp_send_json_error(__('Keyword Suggestions module not available', 'kseo-seo-booster'));
        }
    }
    
    /**
     * AJAX: Bulk audit
     */
    public function ajax_bulk_audit() {
        check_ajax_referer('kseo_nonce', 'nonce');
        if (!current_user_can('kseo_run_audits') && !current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied', 'kseo-seo-booster'));
        }
        
        $bulk_audit = $this->service_loader->get_module('bulk_audit');
        if ($bulk_audit) {
            $result = $bulk_audit->run_audit($_POST);
            wp_send_json_success($result);
        } else {
            wp_send_json_error(__('Bulk Audit module not available', 'kseo-seo-booster'));
        }
    }
} 
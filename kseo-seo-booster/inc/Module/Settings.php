<?php
/**
 * Settings Module for KE SEO Booster Pro
 * 
 * Handles settings page and onboarding wizard.
 * 
 * @package KSEO\SEO_Booster\Module
 */

namespace KSEO\SEO_Booster\Module;

class Settings {
    
    /**
     * Initialize the settings module
     */
    public function __construct() {
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        // Register settings sections
        register_setting('kseo_options', 'kseo_modules');
        register_setting('kseo_options', 'kseo_post_types');
        register_setting('kseo_options', 'kseo_openai_api_key', array(
            'sanitize_callback' => array($this, 'sanitize_encrypt_secret')
        ));
        register_setting('kseo_options', 'kseo_google_ads_credentials', array(
            'sanitize_callback' => array($this, 'sanitize_google_credentials')
        ));
        register_setting('kseo_options', 'kseo_auto_generate');
        register_setting('kseo_options', 'kseo_enable_schema');
        register_setting('kseo_options', 'kseo_enable_og_tags');
        register_setting('kseo_options', 'kseo_onboarding_completed');
        register_setting('kseo_options', 'kseo_feature_flags', array(
            'sanitize_callback' => array($this, 'sanitize_feature_flags')
        ));
        register_setting('kseo_options', 'kseo_rate_limits', array(
            'sanitize_callback' => array($this, 'sanitize_rate_limits')
        ));
        
        // Add settings sections
        add_settings_section(
            'kseo_modules_section',
            __('Modules', 'kseo-seo-booster'),
            array($this, 'modules_section_callback'),
            'kseo_options'
        );
        
        add_settings_section(
            'kseo_api_section',
            __('API Configuration', 'kseo-seo-booster'),
            array($this, 'api_section_callback'),
            'kseo_options'
        );
        
        add_settings_section(
            'kseo_general_section',
            __('General Settings', 'kseo-seo-booster'),
            array($this, 'general_section_callback'),
            'kseo_options'
        );
    }
    
    /**
     * Render the settings page
     */
    public function render() {
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
        
        ?>
        <div class="wrap">
            <h1><?php _e('KE SEO Booster Pro Settings', 'kseo-seo-booster'); ?></h1>
            
            <nav class="nav-tab-wrapper">
                <a href="?page=kseo-settings&tab=general" class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('General', 'kseo-seo-booster'); ?>
                </a>
                <a href="?page=kseo-settings&tab=modules" class="nav-tab <?php echo $active_tab === 'modules' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Modules', 'kseo-seo-booster'); ?>
                </a>
                <a href="?page=kseo-settings&tab=api" class="nav-tab <?php echo $active_tab === 'api' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('API Settings', 'kseo-seo-booster'); ?>
                </a>
                <a href="?page=kseo-settings&tab=onboarding" class="nav-tab <?php echo $active_tab === 'onboarding' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Setup Wizard', 'kseo-seo-booster'); ?>
                </a>
            </nav>
            
            <div class="kseo-settings-content">
                <?php
                switch ($active_tab) {
                    case 'general':
                        $this->render_general_tab();
                        break;
                    case 'modules':
                        $this->render_modules_tab();
                        break;
                    case 'api':
                        $this->render_api_tab();
                        break;
                    case 'onboarding':
                        $this->render_onboarding_tab();
                        break;
                    default:
                        $this->render_general_tab();
                        break;
                }
                ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render general settings tab
     */
    private function render_general_tab() {
        ?>
        <form method="post" action="options.php">
            <?php
            settings_fields('kseo_options');
            do_settings_sections('kseo_options');
            ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="kseo_post_types"><?php _e('Post Types', 'kseo-seo-booster'); ?></label>
                    </th>
                    <td>
                        <?php
                        $post_types = get_post_types(array('public' => true), 'objects');
                        $selected_types = get_option('kseo_post_types', array('post', 'page'));
                        
                        foreach ($post_types as $post_type) {
                            $checked = in_array($post_type->name, $selected_types) ? 'checked' : '';
                            echo '<label><input type="checkbox" name="kseo_post_types[]" value="' . esc_attr($post_type->name) . '" ' . $checked . ' /> ' . esc_html($post_type->label) . '</label><br>';
                        }
                        ?>
                        <p class="description"><?php _e('Select post types to enable SEO optimization.', 'kseo-seo-booster'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="kseo_auto_generate"><?php _e('Auto Generate', 'kseo-seo-booster'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" id="kseo_auto_generate" name="kseo_auto_generate" value="1" <?php checked(get_option('kseo_auto_generate', true), '1'); ?> />
                            <?php _e('Automatically generate SEO meta when saving posts', 'kseo-seo-booster'); ?>
                        </label>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="kseo_enable_schema"><?php _e('Schema Markup', 'kseo-seo-booster'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" id="kseo_enable_schema" name="kseo_enable_schema" value="1" <?php checked(get_option('kseo_enable_schema', true), '1'); ?> />
                            <?php _e('Enable JSON-LD schema markup', 'kseo-seo-booster'); ?>
                        </label>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="kseo_enable_og_tags"><?php _e('Open Graph Tags', 'kseo-seo-booster'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" id="kseo_enable_og_tags" name="kseo_enable_og_tags" value="1" <?php checked(get_option('kseo_enable_og_tags', true), '1'); ?> />
                            <?php _e('Enable Open Graph tags for social media', 'kseo-seo-booster'); ?>
                        </label>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(); ?>
        </form>
        <?php
    }
    
    /**
     * Render modules tab
     */
    private function render_modules_tab() {
        $enabled_modules = get_option('kseo_modules', array());
        ?>
        <form method="post" action="options.php">
            <?php settings_fields('kseo_options'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Available Modules', 'kseo-seo-booster'); ?></th>
                    <td>
                        <?php
                        $modules = array(
                            'meta_box' => __('SEO Meta Box', 'kseo-seo-booster'),
                            'meta_output' => __('Meta Tag Output', 'kseo-seo-booster'),
                            'social_tags' => __('Social Media Tags', 'kseo-seo-booster'),
                            'schema' => __('Schema Markup', 'kseo-seo-booster'),
                            'sitemap' => __('XML Sitemap', 'kseo-seo-booster'),
                            'robots' => __('Robots.txt Editor', 'kseo-seo-booster'),
                            'keyword_suggest' => __('Keyword Suggestions', 'kseo-seo-booster'),
                            'ai_generator' => __('AI Content Generator', 'kseo-seo-booster'),
                            'bulk_audit' => __('Bulk SEO Audit', 'kseo-seo-booster'),
                            'internal_link' => __('Internal Linking', 'kseo-seo-booster')
                        );
                        
                        foreach ($modules as $module_key => $module_name) {
                            $checked = isset($enabled_modules[$module_key]) && $enabled_modules[$module_key] ? 'checked' : '';
                            echo '<label><input type="checkbox" name="kseo_modules[' . esc_attr($module_key) . ']" value="1" ' . $checked . ' /> ' . esc_html($module_name) . '</label><br>';
                        }
                        ?>
                        <p class="description"><?php _e('Enable or disable specific modules. Some modules require API configuration.', 'kseo-seo-booster'); ?></p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(); ?>
        </form>
        <?php
    }
    
    /**
     * Render API settings tab
     */
    private function render_api_tab() {
        ?>
        <form method="post" action="options.php">
            <?php settings_fields('kseo_options'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="kseo_openai_api_key"><?php _e('OpenAI API Key', 'kseo-seo-booster'); ?></label>
                    </th>
                    <td>
                        <input type="password" id="kseo_openai_api_key" name="kseo_openai_api_key" value="" class="regular-text" autocomplete="new-password" />
                        <?php if (get_option('kseo_openai_api_key')): ?>
                            <p class="description"><?php _e('A key is stored. Leave blank to keep.', 'kseo-seo-booster'); ?></p>
                        <?php endif; ?>
                        <button type="button" class="button kseo-test-api-btn" data-api="openai"><?php _e('Test Connection', 'kseo-seo-booster'); ?></button>
                        <p class="description"><?php _e('Enter your OpenAI API key for AI-powered content generation.', 'kseo-seo-booster'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="kseo_google_ads_customer_id"><?php _e('Google Ads Customer ID', 'kseo-seo-booster'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="kseo_google_ads_customer_id" name="kseo_google_ads_credentials[customer_id]" value="<?php echo esc_attr($this->get_google_credential('customer_id')); ?>" class="regular-text" />
                        <p class="description"><?php _e('Your Google Ads customer ID for keyword suggestions.', 'kseo-seo-booster'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="kseo_google_ads_developer_token"><?php _e('Google Ads Developer Token', 'kseo-seo-booster'); ?></label>
                    </th>
                    <td>
                        <input type="password" id="kseo_google_ads_developer_token" name="kseo_google_ads_credentials[developer_token]" value="" class="regular-text" autocomplete="new-password" />
                        <?php if ($this->get_google_credential('developer_token')): ?>
                            <p class="description"><?php _e('A token is stored. Leave blank to keep.', 'kseo-seo-booster'); ?></p>
                        <?php endif; ?>
                        <p class="description"><?php _e('Your Google Ads developer token.', 'kseo-seo-booster'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="kseo_google_ads_client_id"><?php _e('Google Ads Client ID', 'kseo-seo-booster'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="kseo_google_ads_client_id" name="kseo_google_ads_credentials[client_id]" value="<?php echo esc_attr($this->get_google_credential('client_id')); ?>" class="regular-text" />
                        <p class="description"><?php _e('Your Google Ads OAuth client ID.', 'kseo-seo-booster'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="kseo_google_ads_client_secret"><?php _e('Google Ads Client Secret', 'kseo-seo-booster'); ?></label>
                    </th>
                    <td>
                        <input type="password" id="kseo_google_ads_client_secret" name="kseo_google_ads_credentials[client_secret]" value="" class="regular-text" autocomplete="new-password" />
                        <?php if ($this->get_google_credential('client_secret')): ?>
                            <p class="description"><?php _e('A secret is stored. Leave blank to keep.', 'kseo-seo-booster'); ?></p>
                        <?php endif; ?>
                        <button type="button" class="button kseo-test-api-btn" data-api="google"><?php _e('Test Connection', 'kseo-seo-booster'); ?></button>
                        <p class="description"><?php _e('Your Google Ads OAuth client secret.', 'kseo-seo-booster'); ?></p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(); ?>
        </form>

        <?php if (current_user_can('manage_options')): ?>
        <hr/>
        <h2><?php _e('Security', 'kseo-seo-booster'); ?></h2>
        <form method="post">
            <?php wp_nonce_field('kseo_security_nonce', 'kseo_security_nonce'); ?>
            <p>
                <label for="kseo_api_key_label"><?php _e('New API Key Label', 'kseo-seo-booster'); ?></label>
                <input type="text" id="kseo_api_key_label" name="kseo_api_key_label" class="regular-text" />
                <button type="submit" name="kseo_generate_api_key" class="button button-primary"><?php _e('Generate API Key', 'kseo-seo-booster'); ?></button>
            </p>
        </form>
        <?php $this->render_api_keys_table(); endif; ?>

        <?php if (current_user_can('manage_options')): ?>
        <hr/>
        <h2><?php _e('Feature Flags', 'kseo-seo-booster'); ?></h2>
        <form method="post" action="options.php">
            <?php settings_fields('kseo_options'); ?>
            <?php $flags = get_option('kseo_feature_flags', array()); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Enable Rate Limiting', 'kseo-seo-booster'); ?></th>
                    <td><label><input type="checkbox" name="kseo_feature_flags[rate_limit_enabled]" value="1" <?php checked(isset($flags['rate_limit_enabled']) ? (bool)$flags['rate_limit_enabled'] : true); ?> /> <?php _e('Apply per-actor rate limits to heavy endpoints', 'kseo-seo-booster'); ?></label></td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Strict JSON Validation', 'kseo-seo-booster'); ?></th>
                    <td><label><input type="checkbox" name="kseo_feature_flags[strict_json_validation]" value="1" <?php checked(isset($flags['strict_json_validation']) ? (bool)$flags['strict_json_validation'] : true); ?> /> <?php _e('Require application/json and validate payloads', 'kseo-seo-booster'); ?></label></td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Bearer Header Only', 'kseo-seo-booster'); ?></th>
                    <td><label><input type="checkbox" name="kseo_feature_flags[bearer_only]" value="1" <?php checked(isset($flags['bearer_only']) ? (bool)$flags['bearer_only'] : true); ?> /> <?php _e('Accept API keys only via Authorization header', 'kseo-seo-booster'); ?></label></td>
                </tr>
            </table>
            <?php submit_button(__('Save Feature Flags', 'kseo-seo-booster')); ?>
        </form>

        <hr/>
        <h2><?php _e('Rate Limits', 'kseo-seo-booster'); ?></h2>
        <form method="post" action="options.php">
            <?php settings_fields('kseo_options'); ?>
            <?php $limits = get_option('kseo_rate_limits', array()); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><code>POST:/kseo/v1/keywords/research</code></th>
                    <td>
                        <input type="number" min="1" max="10000" name="kseo_rate_limits[POST:/kseo/v1/keywords/research]" value="<?php echo esc_attr(isset($limits['POST:/kseo/v1/keywords/research']) ? (int)$limits['POST:/kseo/v1/keywords/research'] : 60); ?>" />
                        <span class="description"><?php _e('Requests per minute per actor', 'kseo-seo-booster'); ?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><code>POST:/kseo/v1/content/optimize</code></th>
                    <td>
                        <input type="number" min="1" max="10000" name="kseo_rate_limits[POST:/kseo/v1/content/optimize]" value="<?php echo esc_attr(isset($limits['POST:/kseo/v1/content/optimize']) ? (int)$limits['POST:/kseo/v1/content/optimize'] : 60); ?>" />
                        <span class="description"><?php _e('Requests per minute per actor', 'kseo-seo-booster'); ?></span>
                    </td>
                </tr>
            </table>
            <?php submit_button(__('Save Rate Limits', 'kseo-seo-booster')); ?>
        </form>
        <?php endif; ?>
        
        <script>
        jQuery(document).ready(function($) {
            $('.kseo-test-api-btn').on('click', function() {
                var button = $(this);
                var api = button.data('api');
                
                button.prop('disabled', true).text('<?php _e('Testing...', 'kseo-seo-booster'); ?>');
                
                $.ajax({
                    url: kseo_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'kseo_test_api',
                        nonce: kseo_ajax.nonce,
                        api: api
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('<?php _e('API connection successful!', 'kseo-seo-booster'); ?>');
                        } else {
                            alert(response.data);
                        }
                    },
                    error: function() {
                        alert('<?php _e('API connection failed. Please check your credentials.', 'kseo-seo-booster'); ?>');
                    },
                    complete: function() {
                        button.prop('disabled', false).text('<?php _e('Test Connection', 'kseo-seo-booster'); ?>');
                    }
                });
            });
        });
        </script>
        <?php
    }

    public function sanitize_feature_flags($value) {
        $out = array();
        $in = is_array($value) ? $value : array();
        $out['rate_limit_enabled'] = !empty($in['rate_limit_enabled']) ? 1 : 0;
        $out['strict_json_validation'] = !empty($in['strict_json_validation']) ? 1 : 0;
        $out['bearer_only'] = !empty($in['bearer_only']) ? 1 : 0;
        return $out;
    }

    public function sanitize_rate_limits($value) {
        $out = array();
        $in = is_array($value) ? $value : array();
        foreach ($in as $route => $limit) {
            $route = sanitize_text_field($route);
            $limit = max(1, min(10000, (int) $limit));
            $out[$route] = $limit;
        }
        return $out;
    }

    private function render_api_keys_table() {
        global $wpdb;
        $table = $wpdb->prefix . 'kseo_api_key';

        if (isset($_POST['kseo_generate_api_key']) && check_admin_referer('kseo_security_nonce', 'kseo_security_nonce')) {
            $label = isset($_POST['kseo_api_key_label']) ? sanitize_text_field(wp_unslash($_POST['kseo_api_key_label'])) : 'API Key';
            $token = wp_generate_password(64, false, false);
            $hash = hash('sha256', $token);
            $wpdb->insert($table, array(
                'label' => $label,
                'key_hash' => $hash,
                'scopes' => 'read',
                'created_by' => get_current_user_id(),
                'status' => 'active',
                'created_at' => current_time('mysql', 1),
            ));
            echo '<div class="notice notice-success"><p>' . esc_html__('Copy your API key now. It will not be shown again:', 'kseo-seo-booster') . '</p><code style="user-select:all">' . esc_html($token) . '</code></div>';
        }

        $keys = $wpdb->get_results("SELECT id, label, scopes, status, created_at, last_used_at FROM {$table} ORDER BY id DESC LIMIT 50", ARRAY_A);
        echo '<h3>' . esc_html__('API Keys', 'kseo-seo-booster') . '</h3>';
        echo '<table class="widefat"><thead><tr><th>Label</th><th>Scopes</th><th>Status</th><th>Created</th><th>Last Used</th></tr></thead><tbody>';
        foreach ($keys as $k) {
            echo '<tr><td>' . esc_html($k['label']) . '</td><td>' . esc_html($k['scopes']) . '</td><td>' . esc_html($k['status']) . '</td><td>' . esc_html($k['created_at']) . '</td><td>' . esc_html($k['last_used_at'] ?: '-') . '</td></tr>';
        }
        echo '</tbody></table>';
    }
    
    /**
     * Render onboarding tab
     */
    private function render_onboarding_tab() {
        $onboarding_completed = get_option('kseo_onboarding_completed', false);
        
        if ($onboarding_completed) {
            ?>
            <div class="kseo-onboarding-completed">
                <h2><?php _e('Setup Complete!', 'kseo-seo-booster'); ?></h2>
                <p><?php _e('Your KE SEO Booster Pro is configured and ready to use.', 'kseo-seo-booster'); ?></p>
                <a href="<?php echo admin_url('admin.php?page=kseo-dashboard'); ?>" class="button button-primary"><?php _e('Go to Dashboard', 'kseo-seo-booster'); ?></a>
            </div>
            <?php
        } else {
            ?>
            <div class="kseo-onboarding-wizard">
                <h2><?php _e('Welcome to KE SEO Booster Pro!', 'kseo-seo-booster'); ?></h2>
                <p><?php _e('Let\'s get your SEO plugin configured in just a few steps.', 'kseo-seo-booster'); ?></p>
                
                <div class="kseo-onboarding-steps">
                    <div class="kseo-step active" data-step="1">
                        <h3><?php _e('Step 1: Basic Configuration', 'kseo-seo-booster'); ?></h3>
                        <p><?php _e('Configure which post types should have SEO optimization enabled.', 'kseo-seo-booster'); ?></p>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Post Types', 'kseo-seo-booster'); ?></th>
                                <td>
                                    <?php
                                    $post_types = get_post_types(array('public' => true), 'objects');
                                    $selected_types = get_option('kseo_post_types', array('post', 'page'));
                                    
                                    foreach ($post_types as $post_type) {
                                        $checked = in_array($post_type->name, $selected_types) ? 'checked' : '';
                                        echo '<label><input type="checkbox" name="kseo_post_types[]" value="' . esc_attr($post_type->name) . '" ' . $checked . ' /> ' . esc_html($post_type->label) . '</label><br>';
                                    }
                                    ?>
                                </td>
                            </tr>
                        </table>
                        
                        <button type="button" class="button button-primary kseo-next-step"><?php _e('Next Step', 'kseo-seo-booster'); ?></button>
                    </div>
                    
                    <div class="kseo-step" data-step="2">
                        <h3><?php _e('Step 2: API Configuration (Optional)', 'kseo-seo-booster'); ?></h3>
                        <p><?php _e('Configure API keys for advanced features like AI content generation and keyword suggestions.', 'kseo-seo-booster'); ?></p>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('OpenAI API Key', 'kseo-seo-booster'); ?></th>
                                <td>
                                    <input type="password" name="kseo_openai_api_key" value="<?php echo esc_attr(get_option('kseo_openai_api_key')); ?>" class="regular-text" />
                                    <p class="description"><?php _e('Optional: For AI-powered content generation', 'kseo-seo-booster'); ?></p>
                                </td>
                            </tr>
                        </table>
                        
                        <button type="button" class="button kseo-prev-step"><?php _e('Previous', 'kseo-seo-booster'); ?></button>
                        <button type="button" class="button button-primary kseo-next-step"><?php _e('Next Step', 'kseo-seo-booster'); ?></button>
                    </div>
                    
                    <div class="kseo-step" data-step="3">
                        <h3><?php _e('Step 3: Enable Modules', 'kseo-seo-booster'); ?></h3>
                        <p><?php _e('Choose which SEO features you want to enable.', 'kseo-seo-booster'); ?></p>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Core Modules', 'kseo-seo-booster'); ?></th>
                                <td>
                                    <label><input type="checkbox" name="kseo_modules[meta_box]" value="1" checked disabled /> <?php _e('SEO Meta Box', 'kseo-seo-booster'); ?></label><br>
                                    <label><input type="checkbox" name="kseo_modules[meta_output]" value="1" checked disabled /> <?php _e('Meta Tag Output', 'kseo-seo-booster'); ?></label><br>
                                    <label><input type="checkbox" name="kseo_modules[social_tags]" value="1" checked /> <?php _e('Social Media Tags', 'kseo-seo-booster'); ?></label><br>
                                    <label><input type="checkbox" name="kseo_modules[schema]" value="1" checked /> <?php _e('Schema Markup', 'kseo-seo-booster'); ?></label><br>
                                    <label><input type="checkbox" name="kseo_modules[sitemap]" value="1" checked /> <?php _e('XML Sitemap', 'kseo-seo-booster'); ?></label>
                                </td>
                            </tr>
                        </table>
                        
                        <button type="button" class="button kseo-prev-step"><?php _e('Previous', 'kseo-seo-booster'); ?></button>
                        <button type="button" class="button button-primary kseo-complete-setup"><?php _e('Complete Setup', 'kseo-seo-booster'); ?></button>
                    </div>
                </div>
            </div>
            
            <script>
            jQuery(document).ready(function($) {
                $('.kseo-next-step').on('click', function() {
                    var currentStep = $(this).closest('.kseo-step');
                    var nextStep = currentStep.next('.kseo-step');
                    
                    currentStep.removeClass('active');
                    nextStep.addClass('active');
                });
                
                $('.kseo-prev-step').on('click', function() {
                    var currentStep = $(this).closest('.kseo-step');
                    var prevStep = currentStep.prev('.kseo-step');
                    
                    currentStep.removeClass('active');
                    prevStep.addClass('active');
                });
                
                $('.kseo-complete-setup').on('click', function() {
                    // Save settings and mark onboarding as complete
                    $.ajax({
                        url: kseo_ajax.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'kseo_complete_onboarding',
                            nonce: kseo_ajax.nonce,
                            formData: $('.kseo-onboarding-wizard form').serialize()
                        },
                        success: function(response) {
                            if (response.success) {
                                window.location.href = '<?php echo admin_url('admin.php?page=kseo-dashboard'); ?>';
                            }
                        }
                    });
                });
            });
            </script>
            <?php
        }
    }
    
    /**
     * Get Google Ads credential
     * 
     * @param string $key
     * @return string
     */
    private function get_google_credential($key) {
        $credentials = get_option('kseo_google_ads_credentials', array());
        return isset($credentials[$key]) ? $credentials[$key] : '';
    }

    /**
     * Sanitize and encrypt single secret option
     */
    public function sanitize_encrypt_secret($value) {
        $current = get_option('kseo_openai_api_key');
        $value = is_string($value) ? trim($value) : '';
        if ($value === '') {
            return $current; // keep existing
        }
        return 'enc:' . \KSEO\SEO_Booster\Security\Crypto::encrypt($value);
    }

    /**
     * Sanitize and encrypt Google credentials array
     */
    public function sanitize_google_credentials($value) {
        $current = get_option('kseo_google_ads_credentials', array());
        $value = is_array($value) ? $value : array();
        $out = $current;
        // Non-secret fields can be stored as-is
        if (isset($value['customer_id'])) {
            $out['customer_id'] = sanitize_text_field($value['customer_id']);
        }
        if (isset($value['client_id'])) {
            $out['client_id'] = sanitize_text_field($value['client_id']);
        }
        // Secrets: only overwrite when provided
        if (!empty($value['developer_token'])) {
            $out['developer_token'] = 'enc:' . \KSEO\SEO_Booster\Security\Crypto::encrypt(sanitize_text_field($value['developer_token']));
        }
        if (!empty($value['client_secret'])) {
            $out['client_secret'] = 'enc:' . \KSEO\SEO_Booster\Security\Crypto::encrypt(sanitize_text_field($value['client_secret']));
        }
        return $out;
    }
    
    /**
     * Modules section callback
     */
    public function modules_section_callback() {
        echo '<p>' . __('Configure which modules are enabled. Some modules require API configuration.', 'kseo-seo-booster') . '</p>';
    }
    
    /**
     * API section callback
     */
    public function api_section_callback() {
        echo '<p>' . __('Configure API keys for advanced features like AI content generation and keyword suggestions.', 'kseo-seo-booster') . '</p>';
    }
    
    /**
     * General section callback
     */
    public function general_section_callback() {
        echo '<p>' . __('Configure general settings for the SEO plugin.', 'kseo-seo-booster') . '</p>';
    }
} 
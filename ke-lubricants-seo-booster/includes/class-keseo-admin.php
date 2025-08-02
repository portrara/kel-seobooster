<?php
/**
 * Admin Interface Handler for KE Lubricants SEO Booster
 * 
 * Handles all admin interface functionality including dashboard and settings pages.
 * 
 * @package KE_Lubricants_SEO_Booster
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin Interface Handler Class
 * 
 * CRASH PREVENTION: All admin functions are wrapped in try-catch blocks
 * SECURITY: All inputs are validated and outputs are escaped
 * LOGGING: All admin operations are logged for debugging
 */
class KESEO_Admin {

    /**
     * Initialize the admin handler
     */
    public function __construct() {
        try {
            // Log successful initialization
            error_log('KE Lubricants SEO Booster: Admin handler initialized successfully');
            
        } catch (Exception $e) {
            error_log('KE Lubricants SEO Booster: Admin handler initialization failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Render the main dashboard
     * 
     * CRASH PREVENTION: Wrapped in try-catch to prevent admin crashes
     */
    public function render_dashboard() {
        try {
            ?>
            <div class="wrap">
                <h1><?php _e('KE SEO Booster', 'ke-seo-booster'); ?></h1>
                
                <div class="ke-seo-dashboard">
                    <h2><?php _e('Welcome to KE SEO Booster', 'ke-seo-booster'); ?></h2>
                    <p><?php _e('This plugin helps you optimize your content for search engines using AI-powered SEO generation.', 'ke-seo-booster'); ?></p>
                    
                    <div class="ke-seo-stats">
                        <h3><?php _e('Quick Stats', 'ke-seo-booster'); ?></h3>
                        <?php
                        $core = new KESEO_Core();
                        $optimized_posts = $core->get_optimized_posts_count();
                        $total_keywords = $core->get_total_keywords_count();
                        ?>
                        <p><?php _e('Posts optimized:', 'ke-seo-booster'); ?> <strong><?php echo esc_html($optimized_posts); ?></strong></p>
                        <p><?php _e('Total keywords:', 'ke-seo-booster'); ?> <strong><?php echo esc_html($total_keywords); ?></strong></p>
                    </div>

                    <div class="ke-seo-actions">
                        <h3><?php _e('Quick Actions', 'ke-seo-booster'); ?></h3>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=ke-seo-booster-settings')); ?>" class="button button-primary"><?php _e('Configure Settings', 'ke-seo-booster'); ?></a>
                        <a href="<?php echo esc_url(admin_url('edit.php')); ?>" class="button"><?php _e('Manage Posts', 'ke-seo-booster'); ?></a>
                    </div>

                    <div class="ke-seo-status">
                        <h3><?php _e('System Status', 'ke-seo-booster'); ?></h3>
                        <?php
                        $validation = $core->validate_installation();
                        $status_items = array(
                            'php_version' => __('PHP Version', 'ke-seo-booster'),
                            'wp_version' => __('WordPress Version', 'ke-seo-booster'),
                            'memory_limit' => __('Memory Limit', 'ke-seo-booster'),
                            'file_permissions' => __('File Permissions', 'ke-seo-booster'),
                            'database_connection' => __('Database Connection', 'ke-seo-booster')
                        );
                        
                        foreach ($status_items as $key => $label) {
                            $status = isset($validation[$key]) ? $validation[$key] : false;
                            $status_class = $status ? 'status-ok' : 'status-error';
                            $status_text = $status ? __('OK', 'ke-seo-booster') : __('Error', 'ke-seo-booster');
                            ?>
                            <p class="<?php echo esc_attr($status_class); ?>">
                                <span class="status-icon"><?php echo $status ? '✓' : '✗'; ?></span>
                                <?php echo esc_html($label); ?>: <strong><?php echo esc_html($status_text); ?></strong>
                            </p>
                            <?php
                        }
                        ?>
                    </div>
                </div>
            </div>
            <?php
            
        } catch (Exception $e) {
            error_log('KE Lubricants SEO Booster: Dashboard render failed: ' . $e->getMessage());
            echo '<div class="wrap"><h1>' . esc_html__('KE SEO Booster', 'ke-seo-booster') . '</h1>';
            echo '<div class="notice notice-error"><p>' . esc_html__('Error loading dashboard: ', 'ke-seo-booster') . esc_html($e->getMessage()) . '</p></div></div>';
        }
    }

    /**
     * Render the settings page
     * 
     * CRASH PREVENTION: Wrapped in try-catch to prevent admin crashes
     */
    public function render_settings() {
        try {
            $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
            
            ?>
            <div class="wrap">
                <h1><?php _e('KE SEO Booster Settings', 'ke-seo-booster'); ?></h1>
                
                <nav class="nav-tab-wrapper">
                    <a href="?page=ke-seo-booster-settings&tab=general" class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>">
                        <?php _e('General', 'ke-seo-booster'); ?>
                    </a>
                    <a href="?page=ke-seo-booster-settings&tab=api" class="nav-tab <?php echo $active_tab === 'api' ? 'nav-tab-active' : ''; ?>">
                        <?php _e('API Settings', 'ke-seo-booster'); ?>
                    </a>
                    <a href="?page=ke-seo-booster-settings&tab=advanced" class="nav-tab <?php echo $active_tab === 'advanced' ? 'nav-tab-active' : ''; ?>">
                        <?php _e('Advanced', 'ke-seo-booster'); ?>
                    </a>
                </nav>
                
                <div class="keseo-settings-content">
                    <?php
                    switch ($active_tab) {
                        case 'general':
                            $this->render_general_tab();
                            break;
                        case 'api':
                            $this->render_api_tab();
                            break;
                        case 'advanced':
                            $this->render_advanced_tab();
                            break;
                        default:
                            $this->render_general_tab();
                            break;
                    }
                    ?>
                </div>
            </div>
            <?php
            
        } catch (Exception $e) {
            error_log('KE Lubricants SEO Booster: Settings render failed: ' . $e->getMessage());
            echo '<div class="wrap"><h1>' . esc_html__('KE SEO Booster Settings', 'ke-seo-booster') . '</h1>';
            echo '<div class="notice notice-error"><p>' . esc_html__('Error loading settings: ', 'ke-seo-booster') . esc_html($e->getMessage()) . '</p></div></div>';
        }
    }

    /**
     * Render general settings tab
     */
    private function render_general_tab() {
        try {
            ?>
            <form method="post" action="options.php">
                <?php
                settings_fields('ke_seo_options');
                do_settings_sections('ke_seo_options');
                ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="keseo_post_types"><?php _e('Post Types', 'ke-seo-booster'); ?></label>
                        </th>
                        <td>
                            <?php
                            $post_types = get_post_types(array('public' => true), 'objects');
                            $selected_types = get_option('keseo_post_types', array('post', 'page'));
                            
                            foreach ($post_types as $post_type) {
                                $checked = in_array($post_type->name, $selected_types) ? 'checked' : '';
                                ?>
                                <label>
                                    <input type="checkbox" name="keseo_post_types[]" value="<?php echo esc_attr($post_type->name); ?>" <?php echo $checked; ?> />
                                    <?php echo esc_html($post_type->label); ?>
                                </label><br>
                                <?php
                            }
                            ?>
                            <p class="description"><?php _e('Select post types to enable SEO optimization.', 'ke-seo-booster'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="keseo_auto_generate"><?php _e('Auto Generate', 'ke-seo-booster'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" name="keseo_auto_generate" value="1" <?php checked(get_option('keseo_auto_generate', true)); ?> />
                                <?php _e('Automatically generate SEO content when saving posts', 'ke-seo-booster'); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="keseo_enable_schema"><?php _e('Schema Markup', 'ke-seo-booster'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" name="keseo_enable_schema" value="1" <?php checked(get_option('keseo_enable_schema', true)); ?> />
                                <?php _e('Enable automatic schema markup generation', 'ke-seo-booster'); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="keseo_enable_og_tags"><?php _e('Open Graph Tags', 'ke-seo-booster'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" name="keseo_enable_og_tags" value="1" <?php checked(get_option('keseo_enable_og_tags', true)); ?> />
                                <?php _e('Enable automatic Open Graph tag generation', 'ke-seo-booster'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
            <?php
            
        } catch (Exception $e) {
            error_log('KE Lubricants SEO Booster: General tab render failed: ' . $e->getMessage());
            echo '<div class="notice notice-error"><p>' . esc_html__('Error loading general settings: ', 'ke-seo-booster') . esc_html($e->getMessage()) . '</p></div>';
        }
    }

    /**
     * Render API settings tab
     */
    private function render_api_tab() {
        try {
            ?>
            <form method="post" action="options.php">
                <?php
                settings_fields('ke_seo_options');
                ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="keseo_openai_api_key"><?php _e('OpenAI API Key', 'ke-seo-booster'); ?></label>
                        </th>
                        <td>
                            <input type="password" id="keseo_openai_api_key" name="keseo_openai_api_key" value="<?php echo esc_attr(get_option('keseo_openai_api_key')); ?>" class="regular-text" />
                            <button type="button" id="test-api-key" class="button"><?php _e('Test API Key', 'button'); ?></button>
                            <div id="api-test-result"></div>
                            <p class="description"><?php _e('Enter your OpenAI API key for AI-powered SEO generation.', 'ke-seo-booster'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="keseo_google_client_id"><?php _e('Google Client ID', 'ke-seo-booster'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="keseo_google_client_id" name="keseo_google_client_id" value="<?php echo esc_attr($this->get_google_credential('client_id')); ?>" class="regular-text" />
                            <p class="description"><?php _e('Google Ads API Client ID for keyword research.', 'ke-seo-booster'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="keseo_google_client_secret"><?php _e('Google Client Secret', 'ke-seo-booster'); ?></label>
                        </th>
                        <td>
                            <input type="password" id="keseo_google_client_secret" name="keseo_google_client_secret" value="<?php echo esc_attr($this->get_google_credential('client_secret')); ?>" class="regular-text" />
                            <p class="description"><?php _e('Google Ads API Client Secret.', 'ke-seo-booster'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="keseo_google_developer_token"><?php _e('Google Developer Token', 'ke-seo-booster'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="keseo_google_developer_token" name="keseo_google_developer_token" value="<?php echo esc_attr($this->get_google_credential('developer_token')); ?>" class="regular-text" />
                            <p class="description"><?php _e('Google Ads API Developer Token.', 'ke-seo-booster'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="keseo_google_refresh_token"><?php _e('Google Refresh Token', 'ke-seo-booster'); ?></label>
                        </th>
                        <td>
                            <input type="password" id="keseo_google_refresh_token" name="keseo_google_refresh_token" value="<?php echo esc_attr($this->get_google_credential('refresh_token')); ?>" class="regular-text" />
                            <p class="description"><?php _e('Google Ads API Refresh Token.', 'ke-seo-booster'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
            
            <script>
            jQuery(document).ready(function($) {
                $('#test-api-key').on('click', function() {
                    var $button = $(this);
                    var $result = $('#api-test-result');
                    var apiKey = $('#keseo_openai_api_key').val();
                    
                    if (!apiKey) {
                        $result.html('<span class="error"><?php _e('Please enter an API key first.', 'ke-seo-booster'); ?></span>');
                        return;
                    }
                    
                    $button.prop('disabled', true).text('<?php _e('Testing...', 'ke-seo-booster'); ?>');
                    $result.html('');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'keseo_test_api',
                            api_key: apiKey,
                            nonce: '<?php echo wp_create_nonce('keseo_nonce'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                $result.html('<span class="success">' + response.data.message + '</span>');
                            } else {
                                $result.html('<span class="error">' + response.data + '</span>');
                            }
                        },
                        error: function() {
                            $result.html('<span class="error"><?php _e('Test failed. Please try again.', 'ke-seo-booster'); ?></span>');
                        },
                        complete: function() {
                            $button.prop('disabled', false).text('<?php _e('Test API Key', 'ke-seo-booster'); ?>');
                        }
                    });
                });
            });
            </script>
            <?php
            
        } catch (Exception $e) {
            error_log('KE Lubricants SEO Booster: API tab render failed: ' . $e->getMessage());
            echo '<div class="notice notice-error"><p>' . esc_html__('Error loading API settings: ', 'ke-seo-booster') . esc_html($e->getMessage()) . '</p></div>';
        }
    }

    /**
     * Render advanced settings tab
     */
    private function render_advanced_tab() {
        try {
            ?>
            <h3><?php _e('Advanced Settings', 'ke-seo-booster'); ?></h3>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="keseo_clear_cache"><?php _e('Clear Cache', 'ke-seo-booster'); ?></label>
                    </th>
                    <td>
                        <button type="button" id="keseo_clear_cache" class="button"><?php _e('Clear Plugin Cache', 'ke-seo-booster'); ?></button>
                        <p class="description"><?php _e('Clear all cached data and transients.', 'ke-seo-booster'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="keseo_reset_settings"><?php _e('Reset Settings', 'ke-seo-booster'); ?></label>
                    </th>
                    <td>
                        <button type="button" id="keseo_reset_settings" class="button button-secondary"><?php _e('Reset All Settings', 'ke-seo-booster'); ?></button>
                        <p class="description"><?php _e('Reset all plugin settings to default values. This action cannot be undone.', 'ke-seo-booster'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="keseo_export_settings"><?php _e('Export Settings', 'ke-seo-booster'); ?></label>
                    </th>
                    <td>
                        <button type="button" id="keseo_export_settings" class="button"><?php _e('Export Settings', 'ke-seo-booster'); ?></button>
                        <p class="description"><?php _e('Export all plugin settings as a JSON file.', 'ke-seo-booster'); ?></p>
                    </td>
                </tr>
            </table>
            
            <script>
            jQuery(document).ready(function($) {
                $('#keseo_clear_cache').on('click', function() {
                    if (confirm('<?php _e('Are you sure you want to clear the plugin cache?', 'ke-seo-booster'); ?>')) {
                        var $button = $(this);
                        $button.prop('disabled', true).text('<?php _e('Clearing...', 'ke-seo-booster'); ?>');
                        
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'keseo_clear_cache',
                                nonce: '<?php echo wp_create_nonce('keseo_nonce'); ?>'
                            },
                            success: function(response) {
                                if (response.success) {
                                    alert('<?php _e('Cache cleared successfully!', 'ke-seo-booster'); ?>');
                                } else {
                                    alert('<?php _e('Failed to clear cache.', 'ke-seo-booster'); ?>');
                                }
                            },
                            error: function() {
                                alert('<?php _e('Failed to clear cache. Please try again.', 'ke-seo-booster'); ?>');
                            },
                            complete: function() {
                                $button.prop('disabled', false).text('<?php _e('Clear Plugin Cache', 'ke-seo-booster'); ?>');
                            }
                        });
                    }
                });
                
                $('#keseo_reset_settings').on('click', function() {
                    if (confirm('<?php _e('Are you sure you want to reset all settings? This action cannot be undone.', 'ke-seo-booster'); ?>')) {
                        var $button = $(this);
                        $button.prop('disabled', true).text('<?php _e('Resetting...', 'ke-seo-booster'); ?>');
                        
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'keseo_reset_settings',
                                nonce: '<?php echo wp_create_nonce('keseo_nonce'); ?>'
                            },
                            success: function(response) {
                                if (response.success) {
                                    alert('<?php _e('Settings reset successfully!', 'ke-seo-booster'); ?>');
                                    location.reload();
                                } else {
                                    alert('<?php _e('Failed to reset settings.', 'ke-seo-booster'); ?>');
                                }
                            },
                            error: function() {
                                alert('<?php _e('Failed to reset settings. Please try again.', 'ke-seo-booster'); ?>');
                            },
                            complete: function() {
                                $button.prop('disabled', false).text('<?php _e('Reset All Settings', 'ke-seo-booster'); ?>');
                            }
                        });
                    }
                });
            });
            </script>
            <?php
            
        } catch (Exception $e) {
            error_log('KE Lubricants SEO Booster: Advanced tab render failed: ' . $e->getMessage());
            echo '<div class="notice notice-error"><p>' . esc_html__('Error loading advanced settings: ', 'ke-seo-booster') . esc_html($e->getMessage()) . '</p></div>';
        }
    }

    /**
     * Get Google credential value
     * 
     * @param string $key The credential key
     * @return string The credential value
     */
    private function get_google_credential($key) {
        try {
            $credentials = get_option('keseo_google_ads_credentials', array());
            return isset($credentials[$key]) ? $credentials[$key] : '';
        } catch (Exception $e) {
            error_log('KE Lubricants SEO Booster: Failed to get Google credential: ' . $e->getMessage());
            return '';
        }
    }
} 
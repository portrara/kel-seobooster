<?php
/**
 * Meta Box Handler for KE Lubricants SEO Booster
 * 
 * Handles SEO meta box functionality and frontend meta tag output.
 * 
 * @package KE_Lubricants_SEO_Booster
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Meta Box Handler Class
 * 
 * CRASH PREVENTION: All meta box functions are wrapped in try-catch blocks
 * SECURITY: All inputs are validated and outputs are escaped
 * LOGGING: All meta box operations are logged for debugging
 */
class KESEO_Meta_Box {

    /**
     * Initialize the meta box handler
     */
    public function __construct() {
        try {
            // Log successful initialization
            error_log('KE Lubricants SEO Booster: Meta box handler initialized successfully');
            
        } catch (Exception $e) {
            error_log('KE Lubricants SEO Booster: Meta box handler initialization failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Render the SEO meta box
     * 
     * CRASH PREVENTION: Wrapped in try-catch to prevent admin crashes
     * SECURITY: All outputs are properly escaped
     */
    public function render($post) {
        try {
            // Security check
            if (!current_user_can('edit_post', $post->ID)) {
                return;
            }

            wp_nonce_field('ke_seo_meta_box', 'ke_seo_meta_box_nonce');
            
            // Get existing meta values
            $seo_title = get_post_meta($post->ID, '_ke_seo_title', true);
            $seo_description = get_post_meta($post->ID, '_ke_seo_description', true);
            $seo_keywords = get_post_meta($post->ID, '_ke_seo_keywords', true);
            
            ?>
            <div class="ke-seo-meta-box">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="ke_seo_title"><?php _e('SEO Title', 'ke-seo-booster'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="ke_seo_title" name="ke_seo_title" value="<?php echo esc_attr($seo_title); ?>" class="regular-text" maxlength="60" />
                            <span class="character-count"><?php echo esc_html(strlen($seo_title)); ?>/60</span>
                            <button type="button" class="button generate-btn" data-field="title"><?php _e('Generate with AI', 'ke-seo-booster'); ?></button>
                            <p class="description"><?php _e('Enter the SEO title for this post (max 60 characters).', 'ke-seo-booster'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="ke_seo_description"><?php _e('SEO Description', 'ke-seo-booster'); ?></label>
                        </th>
                        <td>
                            <textarea id="ke_seo_description" name="ke_seo_description" rows="3" class="large-text" maxlength="160"><?php echo esc_textarea($seo_description); ?></textarea>
                            <span class="character-count"><?php echo esc_html(strlen($seo_description)); ?>/160</span>
                            <button type="button" class="button generate-btn" data-field="description"><?php _e('Generate with AI', 'ke-seo-booster'); ?></button>
                            <p class="description"><?php _e('Enter the SEO description for this post (max 160 characters).', 'ke-seo-booster'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="ke_seo_keywords"><?php _e('SEO Keywords', 'ke-seo-booster'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="ke_seo_keywords" name="ke_seo_keywords" value="<?php echo esc_attr($seo_keywords); ?>" class="regular-text" />
                            <button type="button" class="button generate-btn" data-field="keywords"><?php _e('Generate with AI', 'ke-seo-booster'); ?></button>
                            <p class="description"><?php _e('Enter the focus keywords for this post.', 'ke-seo-booster'); ?></p>
                        </td>
                    </tr>
                </table>
                <p>
                    <button type="button" class="button button-primary" id="ke_seo_generate"><?php _e('Generate All SEO Content', 'ke-seo-booster'); ?></button>
                    <span class="spinner" style="float: none; margin-top: 0;"></span>
                </p>
                
                <script>
                jQuery(document).ready(function($) {
                    // Character count updates
                    $('#ke_seo_title').on('input', function() {
                        var length = $(this).val().length;
                        $(this).siblings('.character-count').text(length + '/60');
                    });
                    
                    $('#ke_seo_description').on('input', function() {
                        var length = $(this).val().length;
                        $(this).siblings('.character-count').text(length + '/160');
                    });
                    
                    // Generate individual field
                    $('.generate-btn').on('click', function() {
                        var $button = $(this);
                        var field = $button.data('field');
                        var $input = $('#' + 'ke_seo_' + field);
                        
                        if ($input.length === 0) {
                            alert('<?php _e('Field not found.', 'ke-seo-booster'); ?>');
                            return;
                        }
                        
                        $button.prop('disabled', true).text('<?php _e('Generating...', 'ke-seo-booster'); ?>');
                        
                        $.ajax({
                            url: keseo_ajax.ajax_url,
                            type: 'POST',
                            data: {
                                action: 'keseo_generate_seo',
                                post_id: <?php echo intval($post->ID); ?>,
                                field: field,
                                nonce: keseo_ajax.nonce
                            },
                            success: function(response) {
                                if (response.success) {
                                    $input.val(response.data.content);
                                    $input.trigger('input'); // Update character count
                                } else {
                                    alert('<?php _e('Generation failed: ', 'ke-seo-booster'); ?>' + response.data);
                                }
                            },
                            error: function() {
                                alert('<?php _e('Generation failed. Please try again.', 'ke-seo-booster'); ?>');
                            },
                            complete: function() {
                                $button.prop('disabled', false).text('<?php _e('Generate with AI', 'ke-seo-booster'); ?>');
                            }
                        });
                    });
                    
                    // Generate all fields
                    $('#ke_seo_generate').on('click', function() {
                        var $button = $(this);
                        var $spinner = $button.siblings('.spinner');
                        
                        $button.prop('disabled', true);
                        $spinner.show();
                        
                        var fields = ['title', 'description', 'keywords'];
                        var completed = 0;
                        
                        fields.forEach(function(field) {
                            $.ajax({
                                url: keseo_ajax.ajax_url,
                                type: 'POST',
                                data: {
                                    action: 'keseo_generate_seo',
                                    post_id: <?php echo intval($post->ID); ?>,
                                    field: field,
                                    nonce: keseo_ajax.nonce
                                },
                                success: function(response) {
                                    if (response.success) {
                                        var $input = $('#ke_seo_' + field);
                                        $input.val(response.data.content);
                                        $input.trigger('input');
                                    }
                                },
                                complete: function() {
                                    completed++;
                                    if (completed === fields.length) {
                                        $button.prop('disabled', false);
                                        $spinner.hide();
                                    }
                                }
                            });
                        });
                    });
                });
                </script>
            </div>
            <?php
            
        } catch (Exception $e) {
            error_log('KE Lubricants SEO Booster: Meta box render failed: ' . $e->getMessage());
            echo '<p>' . esc_html__('Error loading SEO meta box: ', 'ke-seo-booster') . esc_html($e->getMessage()) . '</p>';
        }
    }

    /**
     * Save SEO meta data
     * 
     * SECURITY: All inputs are sanitized and validated
     * 
     * @param int $post_id The post ID
     */
    public function save($post_id) {
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

            // Save SEO title
            if (isset($_POST['ke_seo_title'])) {
                $seo_title = sanitize_text_field($_POST['ke_seo_title']);
                // Limit to 60 characters
                if (strlen($seo_title) > 60) {
                    $seo_title = substr($seo_title, 0, 57) . '...';
                }
                update_post_meta($post_id, '_ke_seo_title', $seo_title);
            }

            // Save SEO description
            if (isset($_POST['ke_seo_description'])) {
                $seo_description = sanitize_textarea_field($_POST['ke_seo_description']);
                // Limit to 160 characters
                if (strlen($seo_description) > 160) {
                    $seo_description = substr($seo_description, 0, 157) . '...';
                }
                update_post_meta($post_id, '_ke_seo_description', $seo_description);
            }

            // Save SEO keywords
            if (isset($_POST['ke_seo_keywords'])) {
                $seo_keywords = sanitize_text_field($_POST['ke_seo_keywords']);
                update_post_meta($post_id, '_ke_seo_keywords', $seo_keywords);
            }

            // Log successful save
            error_log("KE Lubricants SEO Booster: SEO meta saved for post {$post_id}");
            
        } catch (Exception $e) {
            error_log('KE Lubricants SEO Booster: Meta save failed: ' . $e->getMessage());
        }
    }

    /**
     * Output meta tags in frontend
     * 
     * CRASH PREVENTION: Wrapped in try-catch to prevent site crashes
     * SECURITY: All outputs are properly escaped
     */
    public function output_meta_tags() {
        try {
            global $post;
            
            if (!$post) {
                return;
            }

            // Get SEO meta values
            $seo_title = get_post_meta($post->ID, '_ke_seo_title', true);
            $seo_description = get_post_meta($post->ID, '_ke_seo_description', true);
            $seo_keywords = get_post_meta($post->ID, '_ke_seo_keywords', true);

            // Output meta tags if they exist
            if (!empty($seo_title)) {
                echo '<meta name="title" content="' . esc_attr($seo_title) . '" />' . "\n";
            }
            
            if (!empty($seo_description)) {
                echo '<meta name="description" content="' . esc_attr($seo_description) . '" />' . "\n";
            }
            
            if (!empty($seo_keywords)) {
                echo '<meta name="keywords" content="' . esc_attr($seo_keywords) . '" />' . "\n";
            }

            // Output Open Graph tags if enabled
            if (get_option('keseo_enable_og_tags', true)) {
                $this->output_og_tags($post, $seo_title, $seo_description);
            }

            // Output schema markup if enabled
            if (get_option('keseo_enable_schema', true)) {
                $this->output_schema_markup($post, $seo_title, $seo_description);
            }
            
        } catch (Exception $e) {
            // Silent fail for frontend to prevent site crashes
            error_log('KE Lubricants SEO Booster: Frontend meta output failed: ' . $e->getMessage());
        }
    }

    /**
     * Output Open Graph tags
     * 
     * @param WP_Post $post The post object
     * @param string $seo_title The SEO title
     * @param string $seo_description The SEO description
     */
    private function output_og_tags($post, $seo_title, $seo_description) {
        try {
            $og_title = !empty($seo_title) ? $seo_title : $post->post_title;
            $og_description = !empty($seo_description) ? $seo_description : wp_strip_all_tags($post->post_content);
            $og_description = substr($og_description, 0, 200) . (strlen($og_description) > 200 ? '...' : '');
            $og_url = get_permalink($post->ID);
            $og_type = 'article';
            $og_site_name = get_bloginfo('name');

            // Get featured image
            $og_image = '';
            if (has_post_thumbnail($post->ID)) {
                $image_id = get_post_thumbnail_id($post->ID);
                $image_url = wp_get_attachment_image_url($image_id, 'large');
                if ($image_url) {
                    $og_image = $image_url;
                }
            }

            // Output Open Graph tags
            echo '<meta property="og:title" content="' . esc_attr($og_title) . '" />' . "\n";
            echo '<meta property="og:description" content="' . esc_attr($og_description) . '" />' . "\n";
            echo '<meta property="og:url" content="' . esc_url($og_url) . '" />' . "\n";
            echo '<meta property="og:type" content="' . esc_attr($og_type) . '" />' . "\n";
            echo '<meta property="og:site_name" content="' . esc_attr($og_site_name) . '" />' . "\n";
            
            if (!empty($og_image)) {
                echo '<meta property="og:image" content="' . esc_url($og_image) . '" />' . "\n";
            }

            // Twitter Card tags
            echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
            echo '<meta name="twitter:title" content="' . esc_attr($og_title) . '" />' . "\n";
            echo '<meta name="twitter:description" content="' . esc_attr($og_description) . '" />' . "\n";
            
            if (!empty($og_image)) {
                echo '<meta name="twitter:image" content="' . esc_url($og_image) . '" />' . "\n";
            }
            
        } catch (Exception $e) {
            error_log('KE Lubricants SEO Booster: Open Graph tags failed: ' . $e->getMessage());
        }
    }

    /**
     * Output schema markup
     * 
     * @param WP_Post $post The post object
     * @param string $seo_title The SEO title
     * @param string $seo_description The SEO description
     */
    private function output_schema_markup($post, $seo_title, $seo_description) {
        try {
            $schema = array(
                '@context' => 'https://schema.org',
                '@type' => 'Article',
                'headline' => !empty($seo_title) ? $seo_title : $post->post_title,
                'description' => !empty($seo_description) ? $seo_description : wp_strip_all_tags($post->post_content),
                'url' => get_permalink($post->ID),
                'datePublished' => get_the_date('c', $post->ID),
                'dateModified' => get_the_modified_date('c', $post->ID),
                'author' => array(
                    '@type' => 'Person',
                    'name' => get_the_author_meta('display_name', $post->post_author)
                ),
                'publisher' => array(
                    '@type' => 'Organization',
                    'name' => get_bloginfo('name'),
                    'url' => home_url()
                )
            );

            // Add featured image if available
            if (has_post_thumbnail($post->ID)) {
                $image_id = get_post_thumbnail_id($post->ID);
                $image_url = wp_get_attachment_image_url($image_id, 'large');
                if ($image_url) {
                    $schema['image'] = $image_url;
                }
            }

            // Output schema markup
            echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
            
        } catch (Exception $e) {
            error_log('KE Lubricants SEO Booster: Schema markup failed: ' . $e->getMessage());
        }
    }

    /**
     * Get SEO meta for a post
     * 
     * @param int $post_id The post ID
     * @return array SEO meta data
     */
    public function get_seo_meta($post_id) {
        try {
            return array(
                'title' => get_post_meta($post_id, '_ke_seo_title', true),
                'description' => get_post_meta($post_id, '_ke_seo_description', true),
                'keywords' => get_post_meta($post_id, '_ke_seo_keywords', true)
            );
        } catch (Exception $e) {
            error_log('KE Lubricants SEO Booster: Failed to get SEO meta: ' . $e->getMessage());
            return array(
                'title' => '',
                'description' => '',
                'keywords' => ''
            );
        }
    }

    /**
     * Update SEO meta for a post
     * 
     * @param int $post_id The post ID
     * @param array $meta_data The meta data to update
     * @return bool Success status
     */
    public function update_seo_meta($post_id, $meta_data) {
        try {
            if (isset($meta_data['title'])) {
                update_post_meta($post_id, '_ke_seo_title', sanitize_text_field($meta_data['title']));
            }
            
            if (isset($meta_data['description'])) {
                update_post_meta($post_id, '_ke_seo_description', sanitize_textarea_field($meta_data['description']));
            }
            
            if (isset($meta_data['keywords'])) {
                update_post_meta($post_id, '_ke_seo_keywords', sanitize_text_field($meta_data['keywords']));
            }
            
            error_log("KE Lubricants SEO Booster: SEO meta updated for post {$post_id}");
            return true;
            
        } catch (Exception $e) {
            error_log('KE Lubricants SEO Booster: Failed to update SEO meta: ' . $e->getMessage());
            return false;
        }
    }
} 
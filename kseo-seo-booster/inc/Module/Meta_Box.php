<?php
/**
 * Meta Box Module for KE SEO Booster Pro
 * 
 * Handles SEO meta boxes for posts and pages with AI generation capabilities.
 * 
 * @package KSEO\SEO_Booster\Module
 */

namespace KSEO\SEO_Booster\Module;

/**
 * Meta Box Module Class
 * 
 * @since 2.0.0
 */
class Meta_Box {
    
    /**
     * Initialize the meta box module
     * 
     * @since 2.0.0
     */
    public function __construct() {
        // Meta box is handled by the main Plugin class
    }
    
    /**
     * Render the meta box
     * 
     * @since 2.0.0
     * @param \WP_Post $post The post object.
     */
    public function render($post) {
        if (!$post || !is_object($post)) {
            return;
        }
        
        // Get current meta values with proper defaults
        $seo_title = get_post_meta($post->ID, '_kseo_title', true);
        $seo_description = get_post_meta($post->ID, '_kseo_description', true);
        $seo_keywords = get_post_meta($post->ID, '_kseo_keywords', true);
        $seo_focus_keyword = get_post_meta($post->ID, '_kseo_focus_keyword', true);
        $seo_noindex = get_post_meta($post->ID, '_kseo_noindex', true);
        $seo_nofollow = get_post_meta($post->ID, '_kseo_nofollow', true);
        
        // Add nonce field for security
        wp_nonce_field('kseo_meta_box', 'kseo_meta_box_nonce');
        
        ?>
        <div class="kseo-meta-box">
            <div class="kseo-tabs">
                <button type="button" class="kseo-tab-button active" data-tab="basic"><?php esc_html_e('Basic SEO', 'kseo-seo-booster'); ?></button>
                <button type="button" class="kseo-tab-button" data-tab="advanced"><?php esc_html_e('Advanced', 'kseo-seo-booster'); ?></button>
                <button type="button" class="kseo-tab-button" data-tab="preview"><?php esc_html_e('Preview', 'kseo-seo-booster'); ?></button>
            </div>
            
            <div class="kseo-tab-content active" id="basic-tab">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="kseo_title"><?php esc_html_e('SEO Title', 'kseo-seo-booster'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="kseo_title" name="kseo_title" value="<?php echo esc_attr($seo_title); ?>" class="regular-text" maxlength="60" />
                            <span class="kseo-character-count">0/60</span>
                            <button type="button" class="button kseo-generate-btn" data-field="title"><?php esc_html_e('Generate with AI', 'kseo-seo-booster'); ?></button>
                            <p class="description"><?php esc_html_e('The title that appears in search results. Keep it under 60 characters.', 'kseo-seo-booster'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="kseo_description"><?php esc_html_e('Meta Description', 'kseo-seo-booster'); ?></label>
                        </th>
                        <td>
                            <textarea id="kseo_description" name="kseo_description" rows="3" class="large-text" maxlength="160"><?php echo esc_textarea($seo_description); ?></textarea>
                            <span class="kseo-character-count">0/160</span>
                            <button type="button" class="button kseo-generate-btn" data-field="description"><?php esc_html_e('Generate with AI', 'kseo-seo-booster'); ?></button>
                            <p class="description"><?php esc_html_e('The description that appears in search results. Keep it under 160 characters.', 'kseo-seo-booster'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="kseo_focus_keyword"><?php esc_html_e('Focus Keyword', 'kseo-seo-booster'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="kseo_focus_keyword" name="kseo_focus_keyword" value="<?php echo esc_attr($seo_focus_keyword); ?>" class="regular-text" />
                            <button type="button" class="button kseo-suggest-btn"><?php esc_html_e('Get Suggestions', 'kseo-seo-booster'); ?></button>
                            <p class="description"><?php esc_html_e('The main keyword you want to rank for.', 'kseo-seo-booster'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="kseo_keywords"><?php esc_html_e('Keywords', 'kseo-seo-booster'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="kseo_keywords" name="kseo_keywords" value="<?php echo esc_attr($seo_keywords); ?>" class="regular-text" />
                            <p class="description"><?php esc_html_e('Additional keywords separated by commas.', 'kseo-seo-booster'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="kseo-tab-content" id="advanced-tab">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="kseo_noindex"><?php esc_html_e('Noindex', 'kseo-seo-booster'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="kseo_noindex" name="kseo_noindex" value="1" <?php checked($seo_noindex, '1'); ?> />
                                <?php esc_html_e('Prevent search engines from indexing this page', 'kseo-seo-booster'); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="kseo_nofollow"><?php esc_html_e('Nofollow', 'kseo-seo-booster'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="kseo_nofollow" name="kseo_nofollow" value="1" <?php checked($seo_nofollow, '1'); ?> />
                                <?php esc_html_e('Prevent search engines from following links on this page', 'kseo-seo-booster'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="kseo-tab-content" id="preview-tab">
                <div class="kseo-snippet-preview">
                    <h4><?php esc_html_e('Search Result Preview', 'kseo-seo-booster'); ?></h4>
                    <div class="kseo-snippet">
                        <div class="kseo-snippet-title" id="kseo-preview-title">
                            <?php echo $seo_title ? esc_html($seo_title) : esc_html(get_the_title($post->ID)); ?>
                        </div>
                        <div class="kseo-snippet-url">
                            <?php echo esc_url(get_permalink($post->ID)); ?>
                        </div>
                        <div class="kseo-snippet-description" id="kseo-preview-description">
                            <?php echo $seo_description ? esc_html($seo_description) : esc_html(wp_trim_words(get_the_excerpt($post->ID), 25)); ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="kseo-meta-box-actions">
                <button type="button" class="button button-primary kseo-generate-all-btn"><?php esc_html_e('Generate All with AI', 'kseo-seo-booster'); ?></button>
                <button type="button" class="button kseo-apply-draft-btn"><?php esc_html_e('Apply as Draft', 'kseo-seo-booster'); ?></button>
                <span class="spinner" style="float: none; margin-top: 0;"></span>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Character count
            function updateCharacterCount(field, maxLength) {
                var count = field.val().length;
                field.siblings('.kseo-character-count').text(count + '/' + maxLength);
                
                if (count > maxLength) {
                    field.siblings('.kseo-character-count').addClass('over-limit');
                } else {
                    field.siblings('.kseo-character-count').removeClass('over-limit');
                }
            }
            
            $('#kseo_title').on('input', function() {
                updateCharacterCount($(this), 60);
                $('#kseo-preview-title').text($(this).val() || '<?php echo esc_js(get_the_title($post->ID)); ?>');
            });
            
            $('#kseo_description').on('input', function() {
                updateCharacterCount($(this), 160);
                $('#kseo-preview-description').text($(this).val() || '<?php echo esc_js(wp_trim_words(get_the_excerpt($post->ID), 25)); ?>');
            });
            
            // Tab switching
            $('.kseo-tab-button').on('click', function() {
                var tab = $(this).data('tab');
                $('.kseo-tab-button').removeClass('active');
                $('.kseo-tab-content').removeClass('active');
                $(this).addClass('active');
                $('#' + tab + '-tab').addClass('active');
            });
            
            // Generate with AI
            $('.kseo-generate-btn').on('click', function() {
                var field = $(this).data('field');
                var button = $(this);
                var spinner = button.siblings('.spinner');
                
                button.prop('disabled', true);
                spinner.addClass('is-active');
                
                $.ajax({
                    url: kseo_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'kseo_generate_meta',
                        nonce: kseo_ajax.nonce,
                        field: field,
                        post_id: <?php echo intval($post->ID); ?>,
                        post_title: '<?php echo esc_js(get_the_title($post->ID)); ?>',
                        post_content: '<?php echo esc_js(wp_strip_all_tags(get_post_field('post_content', $post->ID))); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            if (field === 'title') {
                                $('#kseo_title').val(response.data.title);
                                $('#kseo-preview-title').text(response.data.title);
                                updateCharacterCount($('#kseo_title'), 60);
                            } else if (field === 'description') {
                                $('#kseo_description').val(response.data.description);
                                $('#kseo-preview-description').text(response.data.description);
                                updateCharacterCount($('#kseo_description'), 160);
                            }
                        } else {
                            alert(response.data);
                        }
                    },
                    error: function() {
                        alert(kseo_ajax.strings.error);
                    },
                    complete: function() {
                        button.prop('disabled', false);
                        spinner.removeClass('is-active');
                    }
                });
            });
            
            // Generate all with AI
            $('.kseo-generate-all-btn').on('click', function() {
                var button = $(this);
                var spinner = button.siblings('.spinner');
                
                button.prop('disabled', true);
                spinner.addClass('is-active');
                
                $.ajax({
                    url: kseo_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'kseo_generate_meta',
                        nonce: kseo_ajax.nonce,
                        field: 'all',
                        post_id: <?php echo intval($post->ID); ?>,
                        post_title: '<?php echo esc_js(get_the_title($post->ID)); ?>',
                        post_content: '<?php echo esc_js(wp_strip_all_tags(get_post_field('post_content', $post->ID))); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#kseo_title').val(response.data.title);
                            $('#kseo_description').val(response.data.description);
                            $('#kseo_focus_keyword').val(response.data.focus_keyword);
                            $('#kseo-preview-title').text(response.data.title);
                            $('#kseo-preview-description').text(response.data.description);
                            updateCharacterCount($('#kseo_title'), 60);
                            updateCharacterCount($('#kseo_description'), 160);
                        } else {
                            alert(response.data);
                        }
                    },
                    error: function() {
                        alert(kseo_ajax.strings.error);
                    },
                    complete: function() {
                        button.prop('disabled', false);
                        spinner.removeClass('is-active');
                    }
                });
            });
            
            // Apply as Draft
            $('.kseo-apply-draft-btn').on('click', function() {
                var button = $(this);
                var spinner = button.siblings('.spinner');
                button.prop('disabled', true);
                spinner.addClass('is-active');
                var recommendations = {
                    title: $('#kseo_title').val() || $('#kseo-preview-title').text(),
                    meta: { description: $('#kseo_description').val() || $('#kseo-preview-description').text() },
                    outline: []
                };
                $('.kseo-tab-content#basic-tab h2').each(function(){ recommendations.outline.push({h2: $(this).text()}); });
                $.ajax({
                    url: kseo_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'kseo_ai_apply_draft',
                        nonce: kseo_ajax.nonce,
                        post_id: <?php echo intval($post->ID); ?>,
                        recommendations: JSON.stringify(recommendations)
                    },
                    success: function(response){
                        if (response.success) {
                            alert('<?php echo esc_js(__('Draft created with recommendations.', 'kseo-seo-booster')); ?>');
                        } else {
                            alert(response.data || kseo_ajax.strings.error);
                        }
                    },
                    complete: function(){
                        button.prop('disabled', false);
                        spinner.removeClass('is-active');
                    }
                });
            });
            
            // Initialize character counts
            updateCharacterCount($('#kseo_title'), 60);
            updateCharacterCount($('#kseo_description'), 160);
        });
        </script>
        <?php
    }
    
    /**
     * Save meta box data
     * 
     * @since 2.0.0
     * @param int $post_id The post ID.
     */
    public function save($post_id) {
        // Security checks
        if (!isset($_POST['kseo_meta_box_nonce']) || !wp_verify_nonce($_POST['kseo_meta_box_nonce'], 'kseo_meta_box')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save SEO title
        if (isset($_POST['kseo_title'])) {
            update_post_meta($post_id, '_kseo_title', sanitize_text_field($_POST['kseo_title']));
        }
        
        // Save meta description
        if (isset($_POST['kseo_description'])) {
            update_post_meta($post_id, '_kseo_description', sanitize_textarea_field($_POST['kseo_description']));
        }
        
        // Save keywords
        if (isset($_POST['kseo_keywords'])) {
            update_post_meta($post_id, '_kseo_keywords', sanitize_text_field($_POST['kseo_keywords']));
        }
        
        // Save focus keyword
        if (isset($_POST['kseo_focus_keyword'])) {
            update_post_meta($post_id, '_kseo_focus_keyword', sanitize_text_field($_POST['kseo_focus_keyword']));
        }
        
        // Save noindex
        $noindex = isset($_POST['kseo_noindex']) ? '1' : '0';
        update_post_meta($post_id, '_kseo_noindex', $noindex);
        
        // Save nofollow
        $nofollow = isset($_POST['kseo_nofollow']) ? '1' : '0';
        update_post_meta($post_id, '_kseo_nofollow', $nofollow);
        
        // Clear cache
        $this->clear_cache($post_id);
    }
    
    /**
     * Save meta box data via AJAX
     * 
     * @since 2.0.0
     * @param array $data The data to save.
     * @return array Response array.
     */
    public function save_ajax($data) {
        $post_id = intval($data['post_id']);
        
        if (!current_user_can('edit_post', $post_id)) {
            return array('success' => false, 'message' => __('Permission denied', 'kseo-seo-booster'));
        }
        
        // Save the data
        if (isset($data['title'])) {
            update_post_meta($post_id, '_kseo_title', sanitize_text_field($data['title']));
        }
        
        if (isset($data['description'])) {
            update_post_meta($post_id, '_kseo_description', sanitize_textarea_field($data['description']));
        }
        
        if (isset($data['keywords'])) {
            update_post_meta($post_id, '_kseo_keywords', sanitize_text_field($data['keywords']));
        }
        
        if (isset($data['focus_keyword'])) {
            update_post_meta($post_id, '_kseo_focus_keyword', sanitize_text_field($data['focus_keyword']));
        }
        
        // Clear cache
        $this->clear_cache($post_id);
        
        return array('success' => true, 'message' => __('SEO data saved successfully', 'kseo-seo-booster'));
    }
    
    /**
     * Clear cache for a post
     * 
     * @since 2.0.0
     * @param int $post_id The post ID.
     */
    private function clear_cache($post_id) {
        // Clear any caching plugins
        if (function_exists('wp_cache_delete')) {
            wp_cache_delete($post_id, 'post_meta');
        }
        
        // Clear transients
        delete_transient('kseo_meta_' . $post_id);
    }
} 
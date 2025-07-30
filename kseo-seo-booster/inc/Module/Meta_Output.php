<?php
/**
 * Meta Output Module for KE SEO Booster Pro
 * 
 * Handles frontend meta tag output with caching and proper escaping.
 * 
 * @package KSEO\SEO_Booster\Module
 */

namespace KSEO\SEO_Booster\Module;

class Meta_Output {
    
    /**
     * Initialize the meta output module
     */
    public function __construct() {
        // Meta output is handled by the main Plugin class
    }
    
    /**
     * Output meta tags to the frontend
     */
    public function output() {
        // Only output on single posts/pages
        if (!is_singular()) {
            return;
        }
        
        global $post;
        if (!$post) {
            return;
        }
        
        // Check if noindex is set
        $noindex = get_post_meta($post->ID, '_kseo_noindex', true);
        if ($noindex === '1') {
            echo '<meta name="robots" content="noindex" />' . "\n";
            return;
        }
        
        // Get cached meta tags
        $meta_tags = $this->get_cached_meta_tags($post->ID);
        if ($meta_tags) {
            echo $meta_tags;
            return;
        }
        
        // Generate meta tags
        $meta_tags = $this->generate_meta_tags($post);
        
        // Cache the meta tags
        $this->cache_meta_tags($post->ID, $meta_tags);
        
        // Output the meta tags
        echo $meta_tags;
    }
    
    /**
     * Generate meta tags for a post
     * 
     * @param WP_Post $post
     * @return string
     */
    private function generate_meta_tags($post) {
        $meta_tags = '';
        
        // Get SEO data
        $seo_title = get_post_meta($post->ID, '_kseo_title', true);
        $seo_description = get_post_meta($post->ID, '_kseo_description', true);
        $seo_keywords = get_post_meta($post->ID, '_kseo_keywords', true);
        $seo_focus_keyword = get_post_meta($post->ID, '_kseo_focus_keyword', true);
        $nofollow = get_post_meta($post->ID, '_kseo_nofollow', true);
        
        // Title tag
        if ($seo_title) {
            $meta_tags .= '<title>' . esc_html($seo_title) . '</title>' . "\n";
        } else {
            $meta_tags .= '<title>' . esc_html(get_the_title($post->ID)) . ' - ' . esc_html(get_bloginfo('name')) . '</title>' . "\n";
        }
        
        // Meta description
        if ($seo_description) {
            $meta_tags .= '<meta name="description" content="' . esc_attr($seo_description) . '" />' . "\n";
        } else {
            $excerpt = wp_trim_words(get_the_excerpt($post->ID), 25, '...');
            if ($excerpt) {
                $meta_tags .= '<meta name="description" content="' . esc_attr($excerpt) . '" />' . "\n";
            }
        }
        
        // Keywords
        if ($seo_keywords) {
            $meta_tags .= '<meta name="keywords" content="' . esc_attr($seo_keywords) . '" />' . "\n";
        }
        
        // Focus keyword
        if ($seo_focus_keyword) {
            $meta_tags .= '<meta name="focus-keyword" content="' . esc_attr($seo_focus_keyword) . '" />' . "\n";
        }
        
        // Robots meta
        $robots_content = 'index';
        if ($nofollow === '1') {
            $robots_content .= ', nofollow';
        } else {
            $robots_content .= ', follow';
        }
        $meta_tags .= '<meta name="robots" content="' . esc_attr($robots_content) . '" />' . "\n";
        
        // Canonical URL
        $canonical_url = get_permalink($post->ID);
        $meta_tags .= '<link rel="canonical" href="' . esc_url($canonical_url) . '" />' . "\n";
        
        // Author
        $author = get_the_author_meta('display_name', $post->post_author);
        if ($author) {
            $meta_tags .= '<meta name="author" content="' . esc_attr($author) . '" />' . "\n";
        }
        
        // Publication date
        $published_date = get_the_date('c', $post->ID);
        $meta_tags .= '<meta property="article:published_time" content="' . esc_attr($published_date) . '" />' . "\n";
        
        // Modified date
        $modified_date = get_the_modified_date('c', $post->ID);
        $meta_tags .= '<meta property="article:modified_time" content="' . esc_attr($modified_date) . '" />' . "\n";
        
        // Article type
        $meta_tags .= '<meta property="article:type" content="article" />' . "\n";
        
        // Article section
        $categories = get_the_category($post->ID);
        if (!empty($categories)) {
            $meta_tags .= '<meta property="article:section" content="' . esc_attr($categories[0]->name) . '" />' . "\n";
        }
        
        // Article tags
        $tags = get_the_tags($post->ID);
        if ($tags) {
            foreach ($tags as $tag) {
                $meta_tags .= '<meta property="article:tag" content="' . esc_attr($tag->name) . '" />' . "\n";
            }
        }
        
        // Allow other modules to add meta tags
        $meta_tags = apply_filters('kseo_meta_tags', $meta_tags, $post);
        
        return $meta_tags;
    }
    
    /**
     * Get cached meta tags
     * 
     * @param int $post_id
     * @return string|false
     */
    private function get_cached_meta_tags($post_id) {
        $cache_key = 'kseo_meta_' . $post_id;
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        return false;
    }
    
    /**
     * Cache meta tags
     * 
     * @param int $post_id
     * @param string $meta_tags
     */
    private function cache_meta_tags($post_id, $meta_tags) {
        $cache_key = 'kseo_meta_' . $post_id;
        set_transient($cache_key, $meta_tags, 30 * MINUTE_IN_SECONDS); // Cache for 30 minutes
    }
    
    /**
     * Clear cache for a post
     * 
     * @param int $post_id
     */
    public function clear_cache($post_id) {
        $cache_key = 'kseo_meta_' . $post_id;
        delete_transient($cache_key);
    }
    
    /**
     * Get meta data for a post
     * 
     * @param int $post_id
     * @return array
     */
    public function get_meta_data($post_id) {
        return array(
            'title' => get_post_meta($post_id, '_kseo_title', true),
            'description' => get_post_meta($post_id, '_kseo_description', true),
            'keywords' => get_post_meta($post_id, '_kseo_keywords', true),
            'focus_keyword' => get_post_meta($post_id, '_kseo_focus_keyword', true),
            'noindex' => get_post_meta($post_id, '_kseo_noindex', true),
            'nofollow' => get_post_meta($post_id, '_kseo_nofollow', true)
        );
    }
    
    /**
     * Update meta data for a post
     * 
     * @param int $post_id
     * @param array $meta_data
     */
    public function update_meta_data($post_id, $meta_data) {
        if (isset($meta_data['title'])) {
            update_post_meta($post_id, '_kseo_title', sanitize_text_field($meta_data['title']));
        }
        
        if (isset($meta_data['description'])) {
            update_post_meta($post_id, '_kseo_description', sanitize_textarea_field($meta_data['description']));
        }
        
        if (isset($meta_data['keywords'])) {
            update_post_meta($post_id, '_kseo_keywords', sanitize_text_field($meta_data['keywords']));
        }
        
        if (isset($meta_data['focus_keyword'])) {
            update_post_meta($post_id, '_kseo_focus_keyword', sanitize_text_field($meta_data['focus_keyword']));
        }
        
        if (isset($meta_data['noindex'])) {
            update_post_meta($post_id, '_kseo_noindex', $meta_data['noindex'] ? '1' : '0');
        }
        
        if (isset($meta_data['nofollow'])) {
            update_post_meta($post_id, '_kseo_nofollow', $meta_data['nofollow'] ? '1' : '0');
        }
        
        // Clear cache
        $this->clear_cache($post_id);
    }
} 
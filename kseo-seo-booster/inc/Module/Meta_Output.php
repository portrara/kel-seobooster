<?php
/**
 * Meta Output Module for KE SEO Booster Pro
 * 
 * Handles frontend meta tag output with caching and proper escaping.
 * 
 * @package KSEO\SEO_Booster\Module
 */

namespace KSEO\SEO_Booster\Module;

/**
 * Meta Output Module Class
 * 
 * @since 2.0.0
 */
class Meta_Output {
    
    /**
     * Initialize the meta output module
     * 
     * @since 2.0.0
     */
    public function __construct() {
        // Meta output is handled by the main Plugin class
    }
    
    /**
     * Output meta tags to the frontend
     * 
     * @since 2.0.0
     */
    public function output() {
        // Only output on single posts/pages
        if (!is_singular()) {
            return;
        }
        
        global $post;
        if (!$post || !is_object($post)) {
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
        
        // Cache the meta tags for 30 minutes
        $this->cache_meta_tags($post->ID, $meta_tags);
        
        // Output the meta tags
        echo $meta_tags;
    }
    
    /**
     * Generate meta tags for a post
     * 
     * @since 2.0.0
     * @param \WP_Post $post The post object.
     * @return string The generated meta tags HTML.
     */
    public function generate_meta_tags($post) {
        $meta_tags = '';
        
        // Get SEO data
        $seo_title = get_post_meta($post->ID, '_kseo_title', true);
        $seo_description = get_post_meta($post->ID, '_kseo_description', true);
        $seo_keywords = get_post_meta($post->ID, '_kseo_keywords', true);
        $seo_focus_keyword = get_post_meta($post->ID, '_kseo_focus_keyword', true);
        $nofollow = get_post_meta($post->ID, '_kseo_nofollow', true);
        
        // Title tag - use custom SEO title or fallback to post title
        if (!empty($seo_title)) {
            $title = $seo_title;
        } else {
            $title = get_the_title($post->ID);
        }
        
        // Add site name to title if not already present
        $site_name = get_bloginfo('name');
        if (!empty($site_name) && strpos($title, $site_name) === false) {
            $title = $title . ' - ' . $site_name;
        }
        
        $meta_tags .= '<title>' . esc_html($title) . '</title>' . "\n";
        
        // Meta description - use custom SEO description or fallback to excerpt
        if (!empty($seo_description)) {
            $description = $seo_description;
        } else {
            $excerpt = get_the_excerpt($post->ID);
            if (!empty($excerpt)) {
                $description = wp_trim_words($excerpt, 25, '...');
            } else {
                $content = wp_strip_all_tags(get_post_field('post_content', $post->ID));
                $description = wp_trim_words($content, 25, '...');
            }
        }
        
        if (!empty($description)) {
            $meta_tags .= '<meta name="description" content="' . esc_attr($description) . '" />' . "\n";
        }
        
        // Keywords
        if (!empty($seo_keywords)) {
            $meta_tags .= '<meta name="keywords" content="' . esc_attr($seo_keywords) . '" />' . "\n";
        }
        
        // Focus keyword
        if (!empty($seo_focus_keyword)) {
            $meta_tags .= '<meta name="focus-keyword" content="' . esc_attr($seo_focus_keyword) . '" />' . "\n";
        }
        
        // Canonical URL
        $canonical_url = get_permalink($post->ID);
        if (!empty($canonical_url)) {
            $meta_tags .= '<link rel="canonical" href="' . esc_url($canonical_url) . '" />' . "\n";
        }
        
        // Robots meta tag
        $robots_content = array();
        
        if ($nofollow === '1') {
            $robots_content[] = 'nofollow';
        }
        
        // Add noarchive for certain post types if needed
        $post_type = get_post_type($post->ID);
        if (in_array($post_type, array('attachment', 'revision'), true)) {
            $robots_content[] = 'noarchive';
        }
        
        if (!empty($robots_content)) {
            $meta_tags .= '<meta name="robots" content="' . esc_attr(implode(',', $robots_content)) . '" />' . "\n";
        }
        
        // Author meta
        $author_id = get_post_field('post_author', $post->ID);
        if ($author_id) {
            $author_name = get_the_author_meta('display_name', $author_id);
            if (!empty($author_name)) {
                $meta_tags .= '<meta name="author" content="' . esc_attr($author_name) . '" />' . "\n";
            }
        }
        
        // Open Graph tags
        $meta_tags .= $this->generate_og_tags($post, $title, $description);
        
        // Twitter Card tags
        $meta_tags .= $this->generate_twitter_tags($post, $title, $description);
        
        // Allow filtering of meta tags
        $meta_tags = apply_filters('kseo_meta_data', $meta_tags, $post->ID);
        
        return $meta_tags;
    }
    
    /**
     * Generate Open Graph tags
     * 
     * @since 2.0.0
     * @param \WP_Post $post The post object.
     * @param string   $title The title.
     * @param string   $description The description.
     * @return string The Open Graph tags HTML.
     */
    private function generate_og_tags($post, $title, $description) {
        $og_tags = '';
        
        // Basic OG tags
        $og_tags .= '<meta property="og:type" content="article" />' . "\n";
        $og_tags .= '<meta property="og:title" content="' . esc_attr($title) . '" />' . "\n";
        
        if (!empty($description)) {
            $og_tags .= '<meta property="og:description" content="' . esc_attr($description) . '" />' . "\n";
        }
        
        $og_tags .= '<meta property="og:url" content="' . esc_url(get_permalink($post->ID)) . '" />' . "\n";
        $og_tags .= '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '" />' . "\n";
        
        // Featured image
        $featured_image_id = get_post_thumbnail_id($post->ID);
        if ($featured_image_id) {
            $featured_image_url = wp_get_attachment_image_url($featured_image_id, 'large');
            if ($featured_image_url) {
                $og_tags .= '<meta property="og:image" content="' . esc_url($featured_image_url) . '" />' . "\n";
                
                // Get image dimensions
                $image_data = wp_get_attachment_image_src($featured_image_id, 'large');
                if ($image_data && isset($image_data[1]) && isset($image_data[2])) {
                    $og_tags .= '<meta property="og:image:width" content="' . esc_attr($image_data[1]) . '" />' . "\n";
                    $og_tags .= '<meta property="og:image:height" content="' . esc_attr($image_data[2]) . '" />' . "\n";
                }
            }
        }
        
        // Article specific tags
        $og_tags .= '<meta property="article:published_time" content="' . esc_attr(get_the_date('c', $post->ID)) . '" />' . "\n";
        $og_tags .= '<meta property="article:modified_time" content="' . esc_attr(get_the_modified_date('c', $post->ID)) . '" />' . "\n";
        
        // Author
        $author_id = get_post_field('post_author', $post->ID);
        if ($author_id) {
            $author_name = get_the_author_meta('display_name', $author_id);
            if (!empty($author_name)) {
                $og_tags .= '<meta property="article:author" content="' . esc_attr($author_name) . '" />' . "\n";
            }
        }
        
        return $og_tags;
    }
    
    /**
     * Generate Twitter Card tags
     * 
     * @since 2.0.0
     * @param \WP_Post $post The post object.
     * @param string   $title The title.
     * @param string   $description The description.
     * @return string The Twitter Card tags HTML.
     */
    private function generate_twitter_tags($post, $title, $description) {
        $twitter_tags = '';
        
        // Twitter Card type
        $featured_image_id = get_post_thumbnail_id($post->ID);
        $card_type = $featured_image_id ? 'summary_large_image' : 'summary';
        $twitter_tags .= '<meta name="twitter:card" content="' . esc_attr($card_type) . '" />' . "\n";
        
        // Twitter site
        $twitter_site = get_option('kseo_twitter_site', '');
        if (!empty($twitter_site)) {
            $twitter_tags .= '<meta name="twitter:site" content="' . esc_attr($twitter_site) . '" />' . "\n";
        }
        
        // Twitter creator
        $author_id = get_post_field('post_author', $post->ID);
        if ($author_id) {
            $twitter_creator = get_the_author_meta('twitter', $author_id);
            if (!empty($twitter_creator)) {
                $twitter_tags .= '<meta name="twitter:creator" content="' . esc_attr($twitter_creator) . '" />' . "\n";
            }
        }
        
        // Title and description
        $twitter_tags .= '<meta name="twitter:title" content="' . esc_attr($title) . '" />' . "\n";
        
        if (!empty($description)) {
            $twitter_tags .= '<meta name="twitter:description" content="' . esc_attr($description) . '" />' . "\n";
        }
        
        // Image
        if ($featured_image_id) {
            $featured_image_url = wp_get_attachment_image_url($featured_image_id, 'large');
            if ($featured_image_url) {
                $twitter_tags .= '<meta name="twitter:image" content="' . esc_url($featured_image_url) . '" />' . "\n";
            }
        }
        
        return $twitter_tags;
    }
    
    /**
     * Get cached meta tags
     * 
     * @since 2.0.0
     * @param int $post_id The post ID.
     * @return string|false The cached meta tags or false if not found.
     */
    private function get_cached_meta_tags($post_id) {
        $cache_key = 'kseo_meta_' . $post_id;
        return get_transient($cache_key);
    }
    
    /**
     * Cache meta tags for 30 minutes
     * 
     * @since 2.0.0
     * @param int    $post_id The post ID.
     * @param string $meta_tags The meta tags HTML.
     */
    public function cache_meta_tags($post_id, $meta_tags) {
        $cache_key = 'kseo_meta_' . $post_id;
        set_transient($cache_key, $meta_tags, 30 * MINUTE_IN_SECONDS);
    }
    
    /**
     * Clear cache for a post
     * 
     * @since 2.0.0
     * @param int $post_id The post ID.
     */
    public function clear_cache($post_id) {
        $cache_key = 'kseo_meta_' . $post_id;
        delete_transient($cache_key);
    }
    
    /**
     * Get meta data for a post
     * 
     * @since 2.0.0
     * @param int $post_id The post ID.
     * @return array The meta data array.
     */
    public function get_meta_data($post_id) {
        return array(
            'title' => get_post_meta($post_id, '_kseo_title', true),
            'description' => get_post_meta($post_id, '_kseo_description', true),
            'keywords' => get_post_meta($post_id, '_kseo_keywords', true),
            'focus_keyword' => get_post_meta($post_id, '_kseo_focus_keyword', true),
            'noindex' => get_post_meta($post_id, '_kseo_noindex', true),
            'nofollow' => get_post_meta($post_id, '_kseo_nofollow', true),
        );
    }
    
    /**
     * Update meta data for a post
     * 
     * @since 2.0.0
     * @param int   $post_id The post ID.
     * @param array $meta_data The meta data array.
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
<?php
/**
 * WP-CLI Commands for KE SEO Booster Pro
 * 
 * @package KSEO\SEO_Booster\CLI
 */

namespace KSEO\SEO_Booster\CLI;

/**
 * WP-CLI Commands Class
 * 
 * @since 2.0.0
 */
class Commands {
    
    /**
     * Regenerate meta cache for all posts
     * 
     * ## OPTIONS
     * 
     * [--post-type=<post-type>]
     * : Post type to regenerate (default: all)
     * 
     * [--limit=<number>]
     * : Number of posts to process (default: all)
     * 
     * [--dry-run]
     * : Show what would be done without actually doing it
     * 
     * ## EXAMPLES
     * 
     *     wp kseo regenerate_meta
     *     wp kseo regenerate_meta --post-type=post --limit=10
     *     wp kseo regenerate_meta --dry-run
     * 
     * @param array $args Command arguments.
     * @param array $assoc_args Command options.
     */
    public function regenerate_meta($args, $assoc_args) {
        if (!class_exists('WP_CLI')) {
            return;
        }
        
        $post_type = isset($assoc_args['post-type']) ? $assoc_args['post-type'] : 'any';
        $limit = isset($assoc_args['limit']) ? intval($assoc_args['limit']) : -1;
        $dry_run = isset($assoc_args['dry-run']);
        
        \WP_CLI::log('Starting meta regeneration...');
        
        // Get posts
        $query_args = array(
            'post_type' => $post_type,
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'fields' => 'ids',
        );
        
        $posts = get_posts($query_args);
        $total_posts = count($posts);
        
        if ($total_posts === 0) {
            \WP_CLI::warning('No posts found to regenerate.');
            return;
        }
        
        \WP_CLI::log(sprintf('Found %d posts to process.', $total_posts));
        
        if ($dry_run) {
            \WP_CLI::log('DRY RUN - No changes will be made.');
        }
        
        $progress = \WP_CLI\Utils\make_progress_bar('Regenerating meta cache', $total_posts);
        $processed = 0;
        $errors = 0;
        
        foreach ($posts as $post_id) {
            try {
                if (!$dry_run) {
                    // Clear existing cache
                    delete_transient('kseo_meta_' . $post_id);
                    
                    // Get the post object
                    $post = get_post($post_id);
                    if ($post) {
                        // Force regeneration by calling the meta output
                        $meta_output = new \KSEO\SEO_Booster\Module\Meta_Output();
                        $meta_output->clear_cache($post_id);
                        
                        // Generate fresh meta tags (this will cache them)
                        $meta_tags = $this->generate_meta_tags($post);
                        if ($meta_tags) {
                            $meta_output->cache_meta_tags($post_id, $meta_tags);
                        }
                    }
                }
                
                $processed++;
                $progress->tick();
                
            } catch (\Exception $e) {
                $errors++;
                \WP_CLI::warning(sprintf('Error processing post %d: %s', $post_id, $e->getMessage()));
            }
        }
        
        $progress->finish();
        
        \WP_CLI::success(sprintf(
            'Meta regeneration completed. Processed: %d, Errors: %d',
            $processed,
            $errors
        ));
    }
    
    /**
     * Regenerate XML sitemap
     * 
     * ## OPTIONS
     * 
     * [--force]
     * : Force regeneration even if cache is fresh
     * 
     * ## EXAMPLES
     * 
     *     wp kseo regenerate_sitemap
     *     wp kseo regenerate_sitemap --force
     * 
     * @param array $args Command arguments.
     * @param array $assoc_args Command options.
     */
    public function regenerate_sitemap($args, $assoc_args) {
        if (!class_exists('WP_CLI')) {
            return;
        }
        
        $force = isset($assoc_args['force']);
        
        \WP_CLI::log('Starting sitemap regeneration...');
        
        try {
            // Clear existing sitemap cache
            delete_transient('kseo_sitemap');
            delete_transient('kseo_sitemap_index');
            
            // Get enabled post types
            $post_types = get_option('kseo_post_types', array('post', 'page'));
            
            if (empty($post_types)) {
                \WP_CLI::warning('No post types configured for sitemap.');
                return;
            }
            
            \WP_CLI::log(sprintf('Processing post types: %s', implode(', ', $post_types)));
            
            $total_posts = 0;
            $sitemap_content = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $sitemap_content .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
            
            foreach ($post_types as $post_type) {
                $posts = get_posts(array(
                    'post_type' => $post_type,
                    'post_status' => 'publish',
                    'posts_per_page' => -1,
                    'fields' => 'ids',
                ));
                
                $type_count = count($posts);
                $total_posts += $type_count;
                
                \WP_CLI::log(sprintf('Found %d posts of type "%s"', $type_count, $post_type));
                
                foreach ($posts as $post_id) {
                    $permalink = get_permalink($post_id);
                    $lastmod = get_the_modified_date('c', $post_id);
                    
                    $sitemap_content .= '  <url>' . "\n";
                    $sitemap_content .= '    <loc>' . esc_url($permalink) . '</loc>' . "\n";
                    $sitemap_content .= '    <lastmod>' . esc_html($lastmod) . '</lastmod>' . "\n";
                    $sitemap_content .= '    <changefreq>weekly</changefreq>' . "\n";
                    $sitemap_content .= '    <priority>0.8</priority>' . "\n";
                    $sitemap_content .= '  </url>' . "\n";
                }
            }
            
            $sitemap_content .= '</urlset>';
            
            // Cache the sitemap
            set_transient('kseo_sitemap', $sitemap_content, DAY_IN_SECONDS);
            
            \WP_CLI::success(sprintf(
                'Sitemap regenerated successfully with %d URLs.',
                $total_posts
            ));
            
        } catch (\Exception $e) {
            \WP_CLI::error(sprintf('Error regenerating sitemap: %s', $e->getMessage()));
        }
    }
    
    /**
     * Generate meta tags for a post
     * 
     * @since 2.0.0
     * @param \WP_Post $post The post object.
     * @return string|false The generated meta tags or false on error.
     */
    private function generate_meta_tags($post) {
        try {
            $meta_output = new \KSEO\SEO_Booster\Module\Meta_Output();
            return $meta_output->generate_meta_tags($post);
        } catch (\Exception $e) {
            \WP_CLI::warning(sprintf('Error generating meta tags for post %d: %s', $post->ID, $e->getMessage()));
            return false;
        }
    }
} 
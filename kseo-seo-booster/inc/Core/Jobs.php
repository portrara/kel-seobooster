<?php
/**
 * Cron and batch handlers
 *
 * @package KSEO\SEO_Booster\Core
 */

namespace KSEO\SEO_Booster\Core;

if (!defined('ABSPATH')) { exit; }

class Jobs {
    public static function init(): void {
        add_filter('cron_schedules', array(__CLASS__, 'add_weekly'));
        add_action('kseo_ai_weekly_reprocess', array(__CLASS__, 'weekly_reprocess'));
        add_action('init', array(__CLASS__, 'maybe_schedule'));
    }

    public static function add_weekly($schedules) {
        $schedules['kseo_weekly'] = array('interval' => 7 * DAY_IN_SECONDS, 'display' => 'KSEO Weekly');
        return $schedules;
    }

    public static function maybe_schedule(): void {
        $opt = get_option('kseo_ai', array());
        if (!empty($opt['weekly_cron_enabled']) && !wp_next_scheduled('kseo_ai_weekly_reprocess')) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'kseo_weekly', 'kseo_ai_weekly_reprocess');
        }
        if (empty($opt['weekly_cron_enabled']) && wp_next_scheduled('kseo_ai_weekly_reprocess')) {
            wp_clear_scheduled_hook('kseo_ai_weekly_reprocess');
        }
    }

    public static function weekly_reprocess(): void {
        self::process_batch();
    }

    public static function process_batch(): void {
        $opt = get_option('kseo_ai', array());
        $maxUrls = isset($opt['max_urls_per_run']) ? max(1, intval($opt['max_urls_per_run'])) : 10;
        $start = microtime(true);
        $processed = 0;
        $sitemap = home_url('/sitemap.xml');
        $urls = array();
        $resp = wp_remote_get($sitemap, array('timeout' => 5));
        if (!is_wp_error($resp) && wp_remote_retrieve_response_code($resp) === 200) {
            $body = wp_remote_retrieve_body($resp);
            if (preg_match_all('/<loc>([^<]+)<\/loc>/', $body, $m)) {
                $urls = array_slice($m[1], 0, $maxUrls);
            }
        }
        foreach ($urls as $url) {
            if ((microtime(true) - $start) > 20.0) { break; }
            $post_id = url_to_postid($url);
            if (!$post_id) { continue; }
            $post = get_post($post_id);
            if (!$post) { continue; }
            $seed = get_the_title($post_id);
            $analysis = \KSEO\SEO_Booster\Module\Analysis::analyze($post->post_content, $seed, 'en');
            $assignment = array('type' => 'existing_page', 'url' => get_permalink($post_id), 'fit' => 70, 'reasons' => array());
            $payload = array(
                'seed' => $seed,
                'keywords' => array(),
                'analysis' => $analysis,
                'assignment' => $assignment,
                'score_before' => 65,
                'score_after' => 75,
            );
            Storage::save_result($post_id, $payload);
            $processed++;
        }
        // Run detectors and send alerts for new events
        $c = \KSEO\SEO_Booster\Module\Cannibalization::scan_recent();
        $d = \KSEO\SEO_Booster\Module\Decay::scan_recent();
        if ($c > 0) { Alerts::send_for_recent('cannibalization', 5); }
        if ($d > 0) { Alerts::send_for_recent('decay', 5); }
        if ($processed < count($urls)) {
            wp_schedule_single_event(time() + 300, 'kseo_ai_weekly_reprocess');
        }
    }
}



<?php
/**
 * Traffic decay detector (deterministic proxies when GSC absent)
 *
 * @package KSEO\SEO_Booster\Module
 */

namespace KSEO\SEO_Booster\Module;

if (!defined('ABSPATH')) { exit; }

class Decay {
    public static function scan_recent(): int {
        $cached = \KSEO\SEO_Booster\Core\Cache::get('kseo:decay:scan');
        if ($cached) { return 0; }
        \KSEO\SEO_Booster\Core\Cache::set('kseo:decay:scan', 1, 600);

        global $wpdb;
        $table = $wpdb->prefix . 'kseo_ai_keywords';
        $rows = $wpdb->get_results("SELECT post_id, assignment, score_before, score_after, created_at FROM {$table} ORDER BY id DESC LIMIT 200", ARRAY_A);
        $seen = array();
        $events = 0;
        foreach ($rows as $r) {
            $pid = intval($r['post_id']);
            if (isset($seen[$pid])) { continue; }
            $seen[$pid] = true;
            $url = get_permalink($pid);
            // Proxies: clicks_delta from score delta; impressions_delta near 0
            $before = max(1, intval($r['score_before']));
            $after = max(1, intval($r['score_after']));
            $delta = ($after - $before) / max(1, $before);
            // simulate decay if negative >= 30%
            if ($delta <= -0.30) {
                $payload = array(
                    'url' => $url,
                    'top_keywords' => array(),
                    'clicks_delta' => round($delta, 2),
                    'impressions_delta' => 0.02,
                    'suggestion' => 'refresh'
                );
                \KSEO\SEO_Booster\Core\Storage::events_log('decay', $payload);
                $events++;
            }
        }
        return $events;
    }
}



<?php
/**
 * Cannibalization detector
 *
 * @package KSEO\SEO_Booster\Module
 */

namespace KSEO\SEO_Booster\Module;

if (!defined('ABSPATH')) { exit; }

class Cannibalization {
    /**
     * Scan recent assignments and optional GSC signals to detect cannibalization
     */
    public static function scan_recent(): int {
        // Cache guard to avoid duplicate processing
        $cached = \KSEO\SEO_Booster\Core\Cache::get('kseo:cannibal:scan');
        if ($cached) { return 0; }
        \KSEO\SEO_Booster\Core\Cache::set('kseo:cannibal:scan', 1, 600);

        global $wpdb;
        $table = $wpdb->prefix . 'kseo_ai_keywords';
        $rows = $wpdb->get_results("SELECT id, post_id, keywords, assignment, created_at FROM {$table} ORDER BY id DESC LIMIT 500", ARRAY_A);
        $byKeyword = array();
        foreach ($rows as $r) {
            $assignment = json_decode($r['assignment'] ?: '[]', true) ?: array();
            $url = isset($assignment['url']) ? $assignment['url'] : get_permalink((int) $r['post_id']);
            $keywords = json_decode($r['keywords'] ?: '[]', true) ?: array();
            foreach ($keywords as $kw) {
                $norm = self::normalize_keyword((string) $kw);
                if ($norm === '') { continue; }
                if (!isset($byKeyword[$norm])) { $byKeyword[$norm] = array(); }
                $byKeyword[$norm][$url] = true;
            }
        }

        $events = 0;
        foreach ($byKeyword as $norm => $urlSet) {
            $urls = array_keys($urlSet);
            if (count($urls) <= 1) { continue; }
            // If multiple URLs share near-same keyword bucket, flag
            $primary = $urls[0];
            $competing = array_slice($urls, 1);
            $payload = array(
                'primary_url' => $primary,
                'competing_urls' => $competing,
                'keyword' => $norm,
                'recommendation' => self::recommendation($primary, $competing)
            );
            \KSEO\SEO_Booster\Core\Storage::events_log('cannibalization', $payload);
            $events++;
        }
        return $events;
    }

    private static function normalize_keyword(string $kw): string {
        $t = strtolower($kw);
        $t = preg_replace('/[^a-z0-9\s]/', '', $t);
        $t = preg_replace('/\s+/', ' ', trim($t));
        return $t;
    }

    public static function jaccard_tokens(string $a, string $b): float {
        $ta = array_unique(preg_split('/\s+/', self::normalize_keyword($a)) ?: array());
        $tb = array_unique(preg_split('/\s+/', self::normalize_keyword($b)) ?: array());
        if (empty($ta) && empty($tb)) { return 1.0; }
        $inter = array_intersect($ta, $tb);
        $union = array_unique(array_merge($ta, $tb));
        return count($union) ? (count($inter) / count($union)) : 0.0;
    }

    public static function is_near(string $a, string $b): bool {
        if (self::jaccard_tokens($a, $b) >= 0.7) { return true; }
        if (function_exists('levenshtein') && levenshtein(self::normalize_keyword($a), self::normalize_keyword($b)) <= 2) { return true; }
        return false;
    }

    private static function recommendation(string $primary, array $competitors): array {
        $action = 'consolidate';
        $target = $primary;
        $notes = 'Merge overlapping pages and set canonical to primary.';
        return array('action' => $action, 'target_url' => $target, 'notes' => $notes);
    }
}



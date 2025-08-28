<?php
/**
 * Storage helpers for AI keywords and events
 *
 * @package KSEO\SEO_Booster\Core
 */

namespace KSEO\SEO_Booster\Core;

if (!defined('ABSPATH')) { exit; }

class Storage {
    /**
     * Save analysis/assignment result row
     *
     * @param int $post_id
     * @param array $payload associative array with keys: seed, keywords, analysis, assignment, score_before, score_after
     * @return int|false insert id or false
     */
    public static function save_result(int $post_id, array $payload) {
        global $wpdb;
        $table = $wpdb->prefix . 'kseo_ai_keywords';
        $data = array(
            'post_id' => $post_id,
            'seed' => isset($payload['seed']) ? sanitize_text_field($payload['seed']) : null,
            'keywords' => isset($payload['keywords']) ? wp_json_encode($payload['keywords']) : null,
            'analysis' => isset($payload['analysis']) ? wp_json_encode($payload['analysis']) : null,
            'assignment' => isset($payload['assignment']) ? wp_json_encode($payload['assignment']) : null,
            'score_before' => isset($payload['score_before']) ? intval($payload['score_before']) : null,
            'score_after' => isset($payload['score_after']) ? intval($payload['score_after']) : null,
            'created_at' => current_time('mysql', 1),
            'updated_at' => current_time('mysql', 1),
        );
        $formats = array('%d','%s','%s','%s','%s','%d','%d','%s','%s');
        $ok = $wpdb->insert($table, $data, $formats);
        return $ok ? (int) $wpdb->insert_id : false;
    }

    /**
     * Get latest record by post id
     */
    public static function get_latest_by_post(int $post_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'kseo_ai_keywords';
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE post_id = %d ORDER BY id DESC LIMIT 1", $post_id), ARRAY_A);
        if (!$row) { return null; }
        foreach (array('keywords','analysis','assignment') as $j) {
            if (!empty($row[$j])) {
                $decoded = json_decode($row[$j], true);
                $row[$j] = is_array($decoded) ? $decoded : array();
            } else {
                $row[$j] = array();
            }
        }
        return $row;
    }

    /**
     * Log an event row
     *
     * @param string $type
     * @param array $payload expects keys: post_id?, related_post_ids?, details
     */
    public static function events_log(string $type, array $payload): bool {
        global $wpdb;
        $table = $wpdb->prefix . 'kseo_ai_events';
        $data = array(
            'type' => sanitize_text_field($type),
            'post_id' => isset($payload['post_id']) ? intval($payload['post_id']) : null,
            'related_post_ids' => isset($payload['related_post_ids']) ? wp_json_encode(array_map('intval', (array) $payload['related_post_ids'])) : null,
            'details' => wp_json_encode(isset($payload['details']) ? $payload['details'] : $payload),
            'created_at' => current_time('mysql', 1),
        );
        $formats = array('%s','%d','%s','%s','%s');
        return (bool) $wpdb->insert($table, $data, $formats);
    }

    /**
     * List events with basic filters
     */
    public static function events_list(array $args = array()): array {
        global $wpdb;
        $table = $wpdb->prefix . 'kseo_ai_events';
        $type = isset($args['type']) ? sanitize_text_field($args['type']) : '';
        $post_id = isset($args['post_id']) ? intval($args['post_id']) : 0;
        $limit = min(500, max(1, isset($args['limit']) ? intval($args['limit']) : 50));
        $offset = max(0, isset($args['offset']) ? intval($args['offset']) : 0);
        $where = array(); $params = array();
        if ($type) { $where[] = 'type = %s'; $params[] = $type; }
        if ($post_id) { $where[] = 'post_id = %d'; $params[] = $post_id; }
        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        $sql = $wpdb->prepare("SELECT id,type,post_id,related_post_ids,details,created_at FROM {$table} {$whereSql} ORDER BY id DESC LIMIT %d OFFSET %d", array_merge($params, array($limit, $offset)));
        $rows = $wpdb->get_results($sql, ARRAY_A);
        foreach ($rows as &$r) {
            $r['related_post_ids'] = $r['related_post_ids'] ? (json_decode($r['related_post_ids'], true) ?: array()) : array();
            $r['details'] = $r['details'] ? (json_decode($r['details'], true) ?: array()) : array();
        }
        return $rows;
    }
}



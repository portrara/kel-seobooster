<?php
/**
 * Alerts: email and Slack notifications
 *
 * @package KSEO\SEO_Booster\Core
 */

namespace KSEO\SEO_Booster\Core;

if (!defined('ABSPATH')) { exit; }

class Alerts {
    public static function send(string $type, array $payload): void {
        $opt = get_option('kseo_ai', array());
        $key = 'kseo:alert:' . $type . ':' . md5(wp_json_encode(array(
            isset($payload['url']) ? $payload['url'] : '',
            isset($payload['keyword']) ? $payload['keyword'] : '',
            isset($payload['primary_url']) ? $payload['primary_url'] : ''
        )));
        if (get_transient($key)) { return; }
        set_transient($key, 1, DAY_IN_SECONDS);

        $sentAny = false;
        if (!empty($opt['alert_email_enabled'])) {
            $ok = self::email($type, $payload, isset($opt['alert_email_to']) ? $opt['alert_email_to'] : get_option('admin_email'));
            Storage::events_log('alert_sent', array('details' => array('channel' => 'email', 'event_type' => $type, 'ok' => $ok)));
            $sentAny = $sentAny || $ok;
        }
        if (!empty($opt['slack_webhook_url'])) {
            $ok = self::slack($type, $payload, $opt['slack_webhook_url']);
            Storage::events_log('alert_sent', array('details' => array('channel' => 'slack', 'event_type' => $type, 'ok' => $ok)));
            $sentAny = $sentAny || $ok;
        }
    }

    public static function email(string $type, array $payload, string $to): bool {
        $subject = '[KSEO] ' . ucfirst($type) . ' alert';
        $message = 'Type: ' . $type . "\n\n" . wp_json_encode($payload, JSON_PRETTY_PRINT);
        return function_exists('wp_mail') ? (bool) wp_mail($to, $subject, $message) : true;
    }

    public static function slack(string $type, array $payload, string $webhook): bool {
        $body = array('text' => '*KSEO ' . ucfirst($type) . '*\n```' . wp_json_encode($payload, JSON_PRETTY_PRINT) . '```');
        $resp = wp_remote_post($webhook, array(
            'timeout' => 5,
            'headers' => array('Content-Type' => 'application/json'),
            'body' => wp_json_encode($body)
        ));
        if (is_wp_error($resp)) { return false; }
        $code = wp_remote_retrieve_response_code($resp);
        return $code >= 200 && $code < 300;
    }

    public static function send_for_recent(string $type, int $minutes = 5): void {
        $rows = Storage::events_list(array('type' => $type, 'limit' => 100));
        $cutoff = time() - ($minutes * 60);
        foreach ($rows as $r) {
            $ts = strtotime($r['created_at']);
            if ($ts !== false && $ts >= $cutoff) {
                self::send($type, $r['details']);
            }
        }
    }
}



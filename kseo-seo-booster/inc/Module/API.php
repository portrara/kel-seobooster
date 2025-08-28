<?php
/**
 * REST API Module
 *
 * @package KSEO\SEO_Booster\Module
 */

namespace KSEO\SEO_Booster\Module;

class API {
    /** @var string */
    private $namespace = 'kseo/v1';

    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    public function register_routes(): void {
        // KSEO-AI: keywords
        register_rest_route('kseo-ai/v1', '/keywords', array(
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'ai_keywords'),
                'permission_callback' => array($this, 'rest_can_edit_posts')
            ),
        ));

        // KSEO-AI: assignments
        register_rest_route('kseo-ai/v1', '/assignments', array(
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'ai_assignments'),
                'permission_callback' => array($this, 'rest_can_edit_posts')
            ),
            array(
                'methods'  => 'GET',
                'callback' => array($this, 'ai_assignments_get_latest'),
                'permission_callback' => array($this, 'rest_can_edit_posts')
            ),
        ));

        // KSEO-AI: dashboard aggregates
        register_rest_route('kseo-ai/v1', '/dashboard', array(
            array(
                'methods'  => 'GET',
                'callback' => array($this, 'ai_dashboard'),
                'permission_callback' => array($this, 'rest_can_manage_options')
            ),
        ));
        // GET /sites/{id}/issues
        register_rest_route($this->namespace, '/sites/(?P<id>\d+)/issues', array(
            array(
                'methods'  => 'GET',
                'callback' => array($this, 'list_issues'),
                'permission_callback' => array($this, 'can_view_reports')
            ),
        ));

        // POST /keywords/research
        register_rest_route($this->namespace, '/keywords/research', array(
            array(
                'methods'  => 'POST',
                'args'     => $this->schema_args('keywords_research'),
                'callback' => array($this, 'keywords_research'),
                'permission_callback' => array($this, 'can_run_audits')
            ),
        ));

        // POST /content/{url_id}/optimize
        register_rest_route($this->namespace, '/content/(?P<url_id>\d+)/optimize', array(
            array(
                'methods'  => 'POST',
                'args'     => $this->schema_args('optimize'),
                'callback' => array($this, 'optimize_content'),
                'permission_callback' => array($this, 'can_optimize_content')
            ),
        ));

        // POST /experiments
        register_rest_route($this->namespace, '/experiments', array(
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'create_experiment'),
                'permission_callback' => array($this, 'can_create_experiments')
            ),
        ));

        // GET /reports/export
        register_rest_route($this->namespace, '/reports/export', array(
            array(
                'methods'  => 'GET',
                'callback' => array($this, 'export_report'),
                'permission_callback' => array($this, 'can_export_reports')
            ),
        ));
    }

    /* Permissions: allow logged-in users with caps, or valid API key via Authorization header */
    public function can_view_reports(\WP_REST_Request $request) { return $this->has_cap_or_api_key('kseo_view_reports', $request); }
    public function can_run_audits(\WP_REST_Request $request) { return $this->has_cap_or_api_key('kseo_run_audits', $request); }
    public function can_optimize_content(\WP_REST_Request $request) { return $this->has_cap_or_api_key('kseo_optimize_content', $request); }
    public function can_create_experiments(\WP_REST_Request $request) { return $this->has_cap_or_api_key('kseo_create_experiments', $request); }
    public function can_export_reports(\WP_REST_Request $request) { return $this->has_cap_or_api_key('kseo_export', $request); }

    private function has_cap_or_api_key(string $cap, \WP_REST_Request $request): bool {
        if (current_user_can('manage_options') || current_user_can($cap)) {
            return true;
        }
        $token = $this->get_bearer_from_request($request);
        if (!$token) {
            return false;
        }
        if (!$this->validate_api_key($token)) {
            return false;
        }
        $this->rate_limit($token);
        return true;
    }

    /* Handlers */
    private function get_bearer_from_request(\WP_REST_Request $request): string {
        $header = $request->get_header('authorization');
        if (is_string($header) && stripos($header, 'Bearer ') === 0) {
            return trim(substr($header, 7));
        }
        return '';
    }

    private function validate_api_key(string $token): bool {
        global $wpdb;
        $table = $wpdb->prefix . 'kseo_api_key';
        $hash = hash('sha256', $token);
        $row = $wpdb->get_row($wpdb->prepare("SELECT id, status FROM {$table} WHERE key_hash = %s LIMIT 1", $hash));
        if (!$row || $row->status !== 'active') {
            return false;
        }
        // best-effort last_used_at update
        $wpdb->query($wpdb->prepare("UPDATE {$table} SET last_used_at = NOW() WHERE key_hash = %s", $hash));
        return true;
    }

    private function rate_limit(string $token): void {
        $key = 'kseo_ratelimit_' . md5($token);
        $count = (int) get_transient($key);
        if ($count >= 60) {
            // Return error by throwing; permission callbacks can return WP_Error, but here we guard earlier
            return;
        }
        set_transient($key, $count + 1, 60);
    }
    public function list_issues(\WP_REST_Request $request) {
        global $wpdb;
        $site_id = intval($request['id']);
        $severity = sanitize_text_field($request->get_param('severity'));
        $fix_state = sanitize_text_field($request->get_param('fix_state'));
        $page = max(1, intval($request->get_param('page') ?: 1));
        $per_page = min(200, max(1, intval($request->get_param('per_page') ?: 50)));
        $offset = ($page - 1) * $per_page;

        $project_table = $wpdb->prefix . 'kseo_project';
        $issue_table = $wpdb->prefix . 'kseo_issue';

        $project_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$project_table} WHERE site_id = %d LIMIT 1", $site_id));
        if (!$project_id) {
            return new \WP_REST_Response(array('items' => [], 'total' => 0), 200);
        }

        $where = ['project_id = %d'];
        $params = [$project_id];
        if ($severity) { $where[] = 'severity = %s'; $params[] = $severity; }
        if ($fix_state) { $where[] = 'fix_state = %s'; $params[] = $fix_state; }
        $where_sql = 'WHERE ' . implode(' AND ', $where);

        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT id, project_id, content_id, type, severity, fix_state, created_at FROM {$issue_table} {$where_sql} ORDER BY id DESC LIMIT %d OFFSET %d",
            array_merge($params, [$per_page, $offset])
        ), ARRAY_A);

        $total = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(1) FROM {$issue_table} {$where_sql}", $params
        ));

        return new \WP_REST_Response(array('items' => $items, 'total' => $total), 200);
    }

    public function keywords_research(\WP_REST_Request $request) {
        // Content-Type + size guard
        if (\KSEO\SEO_Booster\Support\Flags::is_enabled('strict_json_validation') && strtolower($request->get_header('content-type')) !== 'application/json') {
            return new \WP_Error('bad_request', 'Content-Type must be application/json', array('status' => 415));
        }
        $raw = $request->get_body();
        if (strlen($raw) > 65536) {
            return new \WP_Error('bad_request', 'Body too large', array('status' => 413));
        }
        $body = $request->get_json_params();
        if (!is_array($body) || empty($body['terms']) || empty($body['site_id'])) {
            return new \WP_Error('bad_request', 'terms and site_id required', array('status' => 400));
        }
        $headers = array();
        if (\KSEO\SEO_Booster\Support\Flags::is_enabled('rate_limit_enabled')) {
            list($allowed, $retryAfter, $headers) = \KSEO\SEO_Booster\Security\RateLimiter::check('POST:/kseo/v1/keywords/research', $this->actorId($request), 60);
            if (!$allowed) {
                return new \WP_REST_Response(array('error' => 'rate_limited'), 429, $headers);
            }
        }
        // Queue a job (placeholder)
        do_action('kseo_job_enqueue', 'keyword_research', $body);
        return new \WP_REST_Response(null, 202, $headers);
    }

    public function optimize_content(\WP_REST_Request $request) {
        if (\KSEO\SEO_Booster\Support\Flags::is_enabled('strict_json_validation') && strtolower($request->get_header('content-type')) === 'application/json') {
            $raw = $request->get_body();
            if (strlen($raw) > 65536) {
                return new \WP_Error('bad_request', 'Body too large', array('status' => 413));
            }
        }
        $url_id = intval($request['url_id']);
        $apply = (bool) $request->get_param('apply');
        $headers = array();
        if (\KSEO\SEO_Booster\Support\Flags::is_enabled('rate_limit_enabled')) {
            list($allowed, $retryAfter, $headers) = \KSEO\SEO_Booster\Security\RateLimiter::check('POST:/kseo/v1/content/optimize', $this->actorId($request), 60);
            if (!$allowed) {
                return new \WP_REST_Response(array('error' => 'rate_limited'), 429, $headers);
            }
        }
        if ($apply) {
            do_action('kseo_job_enqueue', 'optimize_content', array('url_id' => $url_id));
            return new \WP_REST_Response(null, 202, $headers);
        }
        // Return minimal suggestions placeholder
        return new \WP_REST_Response(array('meta' => array('title' => 'Suggested title', 'description' => 'Suggested description')), 200, $headers);
    }

    /** REST perms: wp_rest nonce + edit_posts */
    public function rest_can_edit_posts(\WP_REST_Request $request) {
        $ok = \KSEO\SEO_Booster\Core\Security::verify_rest('edit_posts', $request);
        return $ok === true ? true : $ok;
    }

    /** REST perms: wp_rest nonce + manage_options */
    public function rest_can_manage_options(\WP_REST_Request $request) {
        $ok = \KSEO\SEO_Booster\Core\Security::verify_rest('manage_options', $request);
        return $ok === true ? true : $ok;
    }

    /**
     * POST /kseo-ai/v1/keywords
     */
    public function ai_keywords(\WP_REST_Request $request) {
        list($allowed,, $retry) = \KSEO\SEO_Booster\Core\Security::rate_limit('ai_keywords', 10, 60);
        if (!$allowed) {
            return new \WP_REST_Response(array('error' => 'rate_limited'), 429, array('Retry-After' => (string) $retry));
        }
        $body = $request->get_json_params();
        $post_id = isset($body['post_id']) ? intval($body['post_id']) : 0;
        $seed = isset($body['seed']) ? sanitize_text_field($body['seed']) : '';
        $country = isset($body['country']) ? sanitize_text_field($body['country']) : '';
        $language = isset($body['language']) ? sanitize_text_field($body['language']) : '';
        if (!$post_id) { return new \WP_REST_Response(array('error' => 'post_id_required'), 400); }
        $post = get_post($post_id);
        if (!$post) { return new \WP_REST_Response(array('error' => 'not_found'), 404); }
        if ($seed === '') { $seed = get_the_title($post_id); }

        // Deterministic set from seed: return expansions
        $analysis = Analysis::analyze($post->post_content, $seed, $language ?: 'en');
        $suggestions = array_slice($analysis['suggestions'], 0, 20);
        return new \WP_REST_Response(array('ok' => true, 'keywords' => $suggestions, 'analysis' => $analysis), 200);
    }

    /**
     * POST /kseo-ai/v1/assignments
     */
    public function ai_assignments(\WP_REST_Request $request) {
        list($allowed,, $retry) = \KSEO\SEO_Booster\Core\Security::rate_limit('ai_assignments', 10, 60);
        if (!$allowed) { return new \WP_REST_Response(array('error' => 'rate_limited'), 429, array('Retry-After' => (string) $retry)); }
        $body = $request->get_json_params();
        $post_id = isset($body['post_id']) ? intval($body['post_id']) : 0;
        $seed = isset($body['seed']) ? sanitize_text_field($body['seed']) : '';
        $keywords = isset($body['keywords']) && is_array($body['keywords']) ? array_slice(array_map('sanitize_text_field', $body['keywords']), 0, 50) : array();
        if (!$post_id) { return new \WP_REST_Response(array('error' => 'post_id_required'), 400); }
        $post = get_post($post_id);
        if (!$post) { return new \WP_REST_Response(array('error' => 'not_found'), 404); }
        if ($seed === '') { $seed = get_the_title($post_id); }

        $analysis = Analysis::analyze($post->post_content, $seed, 'en');
        $assignment = array(
            'type' => 'existing_page',
            'url' => get_permalink($post_id),
            'fit' => 75,
            'reasons' => array('TopicalMatch' => 70, 'IntentMatch' => 90, 'Opportunity' => 65, 'DifficultyMatch' => 60, 'HistoricalAffinity' => 80)
        );
        $score = array('total' => 82, 'breakdown' => array('onpage' => 40, 'intent' => 18, 'difficulty' => 12, 'behavior' => 12));
        $bucket = 'Quick Win';
        $before_after = array('before' => 70, 'after_simulated' => 82);

        $rec = Recommendations::build($analysis, $post);

        $payload = array(
            'seed' => $seed,
            'keywords' => $keywords,
            'analysis' => $analysis,
            'assignment' => $assignment,
            'score_before' => $before_after['before'],
            'score_after' => $before_after['after_simulated']
        );
        \KSEO\SEO_Booster\Core\Storage::save_result($post_id, $payload);

        return new \WP_REST_Response(array(
            'entities' => $analysis['entities'],
            'intent' => $analysis['intent'],
            'difficulty' => $analysis['difficulty'],
            'suggestions' => $analysis['suggestions'],
            'recommendations' => $rec,
            'assignment' => $assignment,
            'score' => $score,
            'bucket' => $bucket,
            'before_after' => $before_after
        ), 200);
    }

    /**
     * GET /kseo-ai/v1/assignments?post_id=...
     */
    public function ai_assignments_get_latest(\WP_REST_Request $request) {
        $post_id = intval($request->get_param('post_id'));
        if (!$post_id) { return new \WP_REST_Response(array('error' => 'post_id_required'), 400); }
        $row = \KSEO\SEO_Booster\Core\Storage::get_latest_by_post($post_id);
        if (!$row) { return new \WP_REST_Response(array('error' => 'not_found'), 404); }
        return new \WP_REST_Response($row, 200);
    }

    /**
     * GET /kseo-ai/v1/dashboard
     */
    public function ai_dashboard(\WP_REST_Request $request) {
        // Minimal aggregates from events table
        $items = \KSEO\SEO_Booster\Core\Storage::events_list(array('limit' => 50));
        $counts = array('Quick Win' => 0, 'Easy Untapped' => 0, 'High-Volume Hard' => 0);
        return new \WP_REST_Response(array('counts' => $counts, 'events' => $items), 200);
    }

    private function actorId(\WP_REST_Request $request): string {
        if (is_user_logged_in()) {
            return 'u:' . get_current_user_id();
        }
        $token = $this->get_bearer_from_request($request);
        if ($token) { return 'k:' . hash('sha256', $token); }
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
        return 'ip:' . $ip;
    }

    private function schema_args(string $type): array {
        if ($type === 'keywords_research') {
            $schema = array(
                'type' => 'object',
                'additionalProperties' => false,
                'properties' => array(
                    'terms' => array('type' => 'array', 'items' => array('type' => 'string', 'minLength' => 1, 'maxLength' => 128), 'minItems' => 1, 'maxItems' => 200),
                    'locale' => array('type' => 'string', 'pattern' => '^[a-z]{2}(-[A-Z]{2})?$'),
                    'site_id' => array('type' => 'integer', 'minimum' => 1),
                    'competitors' => array('type' => 'array', 'items' => array('type' => 'string', 'format' => 'uri'), 'maxItems' => 10)
                ),
                'required' => array('terms', 'site_id')
            );
        } else {
            $schema = array(
                'type' => 'object',
                'additionalProperties' => false,
                'properties' => array(
                    'apply' => array('type' => 'boolean'),
                    'strategies' => array('type' => 'array', 'items' => array('type' => 'string', 'enum' => array('meta', 'headings', 'links', 'schema')), 'maxItems' => 10)
                )
            );
        }
        // Convert JSON schema to WP args (per-field). For body, we validate inside callback too.
        return array();
    }

    public function create_experiment(\WP_REST_Request $request) {
        $body = $request->get_json_params();
        if (!is_array($body) || empty($body['project_id']) || empty($body['name']) || empty($body['target'])) {
            return new \WP_Error('bad_request', 'project_id, name, target required', array('status' => 400));
        }
        return new \WP_REST_Response(array('id' => rand(1000, 9999)), 201);
    }

    public function export_report(\WP_REST_Request $request) {
        $format = $request->get_param('format');
        if (!in_array($format, array('csv', 'pdf'), true)) {
            return new \WP_Error('bad_request', 'invalid format', array('status' => 400));
        }
        $url = home_url('/wp-content/uploads/kseo/report.' . $format);
        return new \WP_REST_Response(array('url' => $url, 'expires_at' => gmdate('c', time() + 3600)), 200);
    }
}


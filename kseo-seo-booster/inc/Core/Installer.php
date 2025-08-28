<?php
/**
 * Database installer for KE SEO Booster Pro
 *
 * @package KSEO\SEO_Booster\Core
 */

namespace KSEO\SEO_Booster\Core;

use wpdb;

class Installer {
    /**
     * Run database migrations using dbDelta
     */
    public static function install(): void {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $project_table = $wpdb->prefix . 'kseo_project';
        $keyword_table = $wpdb->prefix . 'kseo_keyword';
        $content_table = $wpdb->prefix . 'kseo_content';
        $issue_table = $wpdb->prefix . 'kseo_issue';
        $experiment_table = $wpdb->prefix . 'kseo_experiment';
        $experiment_variant_table = $wpdb->prefix . 'kseo_experiment_variant';
        $job_table = $wpdb->prefix . 'kseo_job';
        $webhook_outbox_table = $wpdb->prefix . 'kseo_webhook_outbox';
        $api_key_table = $wpdb->prefix . 'kseo_api_key';
        $integration_table = $wpdb->prefix . 'kseo_integration';

        $sql = [];

        $sql[] = "CREATE TABLE {$project_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            site_id bigint(20) unsigned NOT NULL,
            locale varchar(20) NOT NULL,
            robots_policies longtext NULL,
            cwv_targets longtext NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY idx_site_locale (site_id, locale)
        ) {$charset_collate};";

        $sql[] = "CREATE TABLE {$keyword_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            project_id bigint(20) unsigned NOT NULL,
            term varchar(512) NOT NULL,
            intent varchar(32) NULL,
            volume int NULL,
            difficulty int NULL,
            serp_features longtext NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY idx_project_term (project_id, term(128)),
            KEY idx_project_volume (project_id, volume)
        ) {$charset_collate};";

        $sql[] = "CREATE TABLE {$content_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            project_id bigint(20) unsigned NOT NULL,
            url longtext NOT NULL,
            status varchar(32) NOT NULL,
            score decimal(5,2) NULL,
            recommendations longtext NULL,
            crawled_at datetime NULL,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY idx_project_status (project_id, status),
            KEY idx_project_crawled (project_id, crawled_at)
        ) {$charset_collate};";

        $sql[] = "CREATE TABLE {$issue_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            project_id bigint(20) unsigned NOT NULL,
            content_id bigint(20) unsigned NULL,
            type varchar(64) NOT NULL,
            severity varchar(16) NOT NULL,
            evidence longtext NULL,
            fix_state varchar(16) NOT NULL DEFAULT 'open',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            resolved_at datetime NULL,
            PRIMARY KEY  (id),
            KEY idx_project_sev (project_id, severity),
            KEY idx_content_type (content_id, type),
            KEY idx_project_fix (project_id, fix_state)
        ) {$charset_collate};";

        $sql[] = "CREATE TABLE {$experiment_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            project_id bigint(20) unsigned NOT NULL,
            name varchar(128) NOT NULL,
            target varchar(256) NOT NULL,
            status varchar(16) NOT NULL,
            metrics longtext NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            decided_at datetime NULL,
            PRIMARY KEY  (id),
            KEY idx_project_status (project_id, status)
        ) {$charset_collate};";

        $sql[] = "CREATE TABLE {$experiment_variant_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            experiment_id bigint(20) unsigned NOT NULL,
            name varchar(64) NOT NULL,
            changes longtext NOT NULL,
            metric_lift decimal(7,4) NULL,
            PRIMARY KEY  (id),
            KEY idx_exp (experiment_id)
        ) {$charset_collate};";

        $sql[] = "CREATE TABLE {$job_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            project_id bigint(20) unsigned NOT NULL,
            type varchar(64) NOT NULL,
            payload longtext NULL,
            status varchar(16) NOT NULL DEFAULT 'queued',
            available_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            reserved_at datetime NULL,
            completed_at datetime NULL,
            PRIMARY KEY  (id),
            KEY idx_project_status_time (project_id, status, available_at),
            KEY idx_status_available (status, available_at)
        ) {$charset_collate};";

        $sql[] = "CREATE TABLE {$webhook_outbox_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            project_id bigint(20) unsigned NOT NULL,
            name varchar(64) NOT NULL,
            payload longtext NOT NULL,
            status varchar(16) NOT NULL DEFAULT 'pending',
            attempts int NOT NULL DEFAULT 0,
            next_attempt_at datetime NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY idx_status_next (status, next_attempt_at),
            KEY idx_project_name (project_id, name)
        ) {$charset_collate};";

        $sql[] = "CREATE TABLE {$api_key_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            label varchar(128) NOT NULL,
            key_hash varchar(128) NOT NULL,
            scopes varchar(255) NOT NULL DEFAULT 'read',
            last_used_at datetime NULL,
            created_by bigint(20) unsigned NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            status varchar(16) NOT NULL DEFAULT 'active',
            PRIMARY KEY (id),
            KEY idx_status_time (status, created_at),
            KEY idx_key_hash (key_hash)
        ) {$charset_collate};";

        $sql[] = "CREATE TABLE {$integration_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            project_id bigint(20) unsigned NOT NULL,
            provider varchar(64) NOT NULL,
            data longtext NULL,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_project_provider (project_id, provider)
        ) {$charset_collate};";

        foreach ($sql as $statement) {
            dbDelta($statement);
        }

        // Create AI tables
        $ai_keywords = $wpdb->prefix . 'kseo_ai_keywords';
        $ai_events = $wpdb->prefix . 'kseo_ai_events';

        $sql2 = [];
        $sql2[] = "CREATE TABLE {$ai_keywords} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            post_id bigint(20) unsigned NOT NULL,
            seed varchar(255) NULL,
            keywords longtext NULL,
            analysis longtext NULL,
            assignment longtext NULL,
            score_before tinyint unsigned NULL,
            score_after tinyint unsigned NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY idx_post_id (post_id),
            KEY idx_created (created_at)
        ) {$charset_collate};";

        $sql2[] = "CREATE TABLE {$ai_events} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            type varchar(40) NOT NULL,
            post_id bigint(20) unsigned NULL,
            related_post_ids longtext NULL,
            details longtext NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY idx_type (type),
            KEY idx_post (post_id),
            KEY idx_created (created_at)
        ) {$charset_collate};";

        foreach ($sql2 as $statement) {
            dbDelta($statement);
        }
    }
}


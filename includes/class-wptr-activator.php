<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPTR_Activator {

	public static function activate(): void {
		self::create_tables();
		self::set_defaults();
		WPTR_Cron::schedule();
		flush_rewrite_rules();
	}

	private static function create_tables(): void {
		global $wpdb;
		$charset = $wpdb->get_charset_collate();

		$t_reviews = $wpdb->prefix . 'wptr_reviews';
		$t_groups  = $wpdb->prefix . 'wptr_groups';
		$t_pivot   = $wpdb->prefix . 'wptr_group_reviews';

		$sql = [];

		$sql[] = "CREATE TABLE {$t_reviews} (
			id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			external_id VARCHAR(255)    NOT NULL,
			provider    VARCHAR(50)     NOT NULL DEFAULT 'trustedshops',
			rating      DECIMAL(2,1)    NOT NULL DEFAULT 0,
			title       TEXT,
			comment     TEXT,
			author_name VARCHAR(255)    DEFAULT '',
			submitted_at DATETIME       NULL,
			synced_at   DATETIME        DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY external_id_provider (external_id, provider),
			KEY rating (rating),
			KEY submitted_at (submitted_at),
			KEY provider (provider)
		) {$charset};";

		$sql[] = "CREATE TABLE {$t_groups} (
			id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			name       VARCHAR(255)    NOT NULL,
			slug       VARCHAR(255)    NOT NULL,
			created_at DATETIME        DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY slug (slug)
		) {$charset};";

		$sql[] = "CREATE TABLE {$t_pivot} (
			group_id   BIGINT UNSIGNED NOT NULL,
			review_id  BIGINT UNSIGNED NOT NULL,
			sort_order INT UNSIGNED    DEFAULT 0,
			PRIMARY KEY (group_id, review_id),
			KEY review_id (review_id)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		foreach ( $sql as $query ) {
			dbDelta( $query );
		}

		update_option( 'wptr_db_version', WPTR_VERSION );
	}

	private static function set_defaults(): void {
		add_option( 'wptr_client_id', '' );
		add_option( 'wptr_client_secret', '' );
		add_option( 'wptr_tsid', '' );
		add_option( 'wptr_sync_interval', 'twicedaily' );
		add_option( 'wptr_last_sync', '' );
		add_option( 'wptr_review_count', 0 );
	}
}

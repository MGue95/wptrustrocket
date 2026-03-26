<?php
/**
 * WPTrustRocket – Clean uninstall.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

require_once __DIR__ . '/includes/class-wptr-db.php';

WPTR_DB::drop_tables();

$options = [
	'wptr_client_id',
	'wptr_client_secret',
	'wptr_tsid',
	'wptr_sync_interval',
	'wptr_last_sync',
	'wptr_review_count',
	'wptr_db_version',
];

foreach ( $options as $option ) {
	delete_option( $option );
}

wp_clear_scheduled_hook( 'wptr_sync_reviews' );

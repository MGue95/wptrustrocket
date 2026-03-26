<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPTR_Cron {

	private const HOOK = 'wptr_sync_reviews';

	public function __construct() {
		add_action( self::HOOK, [ __CLASS__, 'run_sync' ] );

		// Register custom "weekly" interval if not already present.
		add_filter( 'cron_schedules', [ __CLASS__, 'add_schedules' ] );
	}

	public static function add_schedules( array $schedules ): array {
		if ( ! isset( $schedules['weekly'] ) ) {
			$schedules['weekly'] = [
				'interval' => WEEK_IN_SECONDS,
				'display'  => __( 'Woechentlich', 'wptrustrocket' ),
			];
		}
		return $schedules;
	}

	public static function schedule(): void {
		if ( ! wp_next_scheduled( self::HOOK ) ) {
			$interval = get_option( 'wptr_sync_interval', 'twicedaily' );
			wp_schedule_event( time(), $interval, self::HOOK );
		}
	}

	public static function unschedule(): void {
		$ts = wp_next_scheduled( self::HOOK );
		if ( $ts ) {
			wp_unschedule_event( $ts, self::HOOK );
		}
	}

	public static function reschedule(): void {
		self::unschedule();
		self::schedule();
	}

	public static function run_sync(): void {
		$result = WPTR_API::sync_reviews();
		if ( ! empty( $result['error'] ) ) {
			error_log( '[WPTrustRocket] Sync failed: ' . $result['error'] );
		}
	}

	public static function get_next_sync(): ?string {
		$next = wp_next_scheduled( self::HOOK );
		return $next ? gmdate( 'Y-m-d H:i:s', $next ) : null;
	}
}

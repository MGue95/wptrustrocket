<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPTR_API {

	private const TOKEN_TRANSIENT = 'wptr_access_token';
	private const TOKEN_URL       = 'https://login.etrusted.com/oauth/token';
	private const API_BASE        = 'https://api.etrusted.com';

	/**
	 * Get OAuth access token (cached for 4 min).
	 */
	public static function get_access_token() {
		$cached = get_transient( self::TOKEN_TRANSIENT );
		if ( $cached ) {
			return $cached;
		}

		$client_id     = get_option( 'wptr_client_id', '' );
		$client_secret = get_option( 'wptr_client_secret', '' );

		if ( empty( $client_id ) || empty( $client_secret ) ) {
			return new \WP_Error( 'wptr_no_credentials', __( 'Client ID und Client Secret muessen konfiguriert sein.', 'wptrustrocket' ) );
		}

		$response = wp_remote_post( self::TOKEN_URL, [
			'timeout' => 15,
			'body'    => [
				'grant_type'    => 'client_credentials',
				'client_id'     => $client_id,
				'client_secret' => $client_secret,
				'audience'      => self::API_BASE,
			],
		] );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code !== 200 || empty( $body['access_token'] ) ) {
			$msg = $body['error_description'] ?? $body['error'] ?? __( 'Token-Anfrage fehlgeschlagen.', 'wptrustrocket' );
			return new \WP_Error( 'wptr_token_error', $msg );
		}

		$token = $body['access_token'];
		set_transient( self::TOKEN_TRANSIENT, $token, 4 * MINUTE_IN_SECONDS );

		return $token;
	}

	/**
	 * Sync all Trusted Shops reviews into the database.
	 */
	public static function sync_reviews(): array {
		$token = self::get_access_token();
		if ( is_wp_error( $token ) ) {
			return [ 'error' => $token->get_error_message() ];
		}

		$tsid = get_option( 'wptr_tsid', '' );
		$url  = self::API_BASE . '/reviews?count=20';

		if ( ! empty( $tsid ) ) {
			$url .= '&channelRef=' . urlencode( $tsid );
		}

		$all_reviews = [];
		$page        = 0;
		$max_pages   = 100;

		while ( $url && $page < $max_pages ) {
			$response = wp_remote_get( $url, [
				'timeout' => 30,
				'headers' => [
					'Authorization' => 'Bearer ' . $token,
					'Accept'        => 'application/json',
				],
			] );

			if ( is_wp_error( $response ) ) {
				return [ 'error' => $response->get_error_message() ];
			}

			$code = wp_remote_retrieve_response_code( $response );
			if ( $code !== 200 ) {
				return [ 'error' => sprintf( __( 'API-Fehler: HTTP %d', 'wptrustrocket' ), $code ) ];
			}

			$body  = json_decode( wp_remote_retrieve_body( $response ), true );
			$items = $body['items'] ?? [];

			foreach ( $items as $item ) {
				$all_reviews[] = $item;
			}

			// Only follow next-page URLs pointing to the known API domain (SSRF protection).
			$next = $body['paging']['links']['next'] ?? null;
			$url  = ( $next && strpos( $next, self::API_BASE ) === 0 ) ? $next : null;
			$page++;
		}

		// Store in database.
		$synced = 0;
		foreach ( $all_reviews as $review ) {
			$id = $review['id'] ?? '';
			if ( empty( $id ) ) {
				continue;
			}

			$submitted      = $review['submittedAt'] ?? $review['createdAt'] ?? '';
			$submitted_date = ! empty( $submitted ) ? gmdate( 'Y-m-d H:i:s', strtotime( $submitted ) ) : null;

			WPTR_DB::upsert_review( [
				'external_id'  => $id,
				'provider'     => 'trustedshops',
				'rating'       => (float) ( $review['rating'] ?? 0 ),
				'title'        => $review['title'] ?? '',
				'comment'      => $review['comment'] ?? '',
				'author_name'  => $review['customer']['fullName'] ?? '',
				'submitted_at' => $submitted_date,
			] );
			$synced++;
		}

		update_option( 'wptr_last_sync', current_time( 'mysql' ) );
		update_option( 'wptr_review_count', WPTR_DB::count_reviews() );

		return [
			'fetched' => count( $all_reviews ),
			'synced'  => $synced,
		];
	}

	public static function clear_token_cache(): void {
		delete_transient( self::TOKEN_TRANSIENT );
	}

	/**
	 * Test API credentials.
	 */
	public static function test_connection() {
		self::clear_token_cache();
		$token = self::get_access_token();
		if ( is_wp_error( $token ) ) {
			return $token;
		}
		return true;
	}
}

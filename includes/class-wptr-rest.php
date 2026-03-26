<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPTR_Rest {

	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	public function register_routes(): void {
		$ns = 'wptrustrocket/v1';

		register_rest_route( $ns, '/sync', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'sync' ],
			'permission_callback' => [ $this, 'admin_check' ],
		] );

		register_rest_route( $ns, '/test-connection', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'test_connection' ],
			'permission_callback' => [ $this, 'admin_check' ],
		] );

		register_rest_route( $ns, '/groups', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'create_group' ],
			'permission_callback' => [ $this, 'admin_check' ],
		] );

		register_rest_route( $ns, '/groups/(?P<id>\d+)', [
			'methods'             => 'DELETE',
			'callback'            => [ $this, 'delete_group' ],
			'permission_callback' => [ $this, 'admin_check' ],
		] );

		register_rest_route( $ns, '/groups/(?P<id>\d+)/reviews', [
			'methods'             => 'PUT',
			'callback'            => [ $this, 'save_group_reviews' ],
			'permission_callback' => [ $this, 'admin_check' ],
		] );

		register_rest_route( $ns, '/reviews', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_reviews' ],
			'permission_callback' => '__return_true',
		] );
	}

	public function admin_check(): bool {
		return current_user_can( 'manage_options' );
	}

	public function sync( \WP_REST_Request $request ): \WP_REST_Response {
		$result = WPTR_API::sync_reviews();

		if ( ! empty( $result['error'] ) ) {
			return new \WP_REST_Response( [
				'success' => false,
				'message' => $result['error'],
			], 500 );
		}

		return new \WP_REST_Response( [
			'success' => true,
			'data'    => $result,
			'total'   => WPTR_DB::count_reviews(),
		] );
	}

	public function test_connection( \WP_REST_Request $request ): \WP_REST_Response {
		// Use credentials from request for testing WITHOUT persisting them.
		$client_id     = sanitize_text_field( $request->get_param( 'client_id' ) ?? '' );
		$client_secret = sanitize_text_field( $request->get_param( 'client_secret' ) ?? '' );

		// Temporarily override options for the test (not saved to DB).
		if ( ! empty( $client_id ) ) {
			add_filter( 'pre_option_wptr_client_id', function () use ( $client_id ) { return $client_id; } );
		}
		if ( ! empty( $client_secret ) ) {
			add_filter( 'pre_option_wptr_client_secret', function () use ( $client_secret ) { return $client_secret; } );
		}

		WPTR_API::clear_token_cache();
		$result = WPTR_API::test_connection();

		if ( is_wp_error( $result ) ) {
			return new \WP_REST_Response( [
				'success' => false,
				'message' => $result->get_error_message(),
			], 400 );
		}

		return new \WP_REST_Response( [ 'success' => true ] );
	}

	public function create_group( \WP_REST_Request $request ): \WP_REST_Response {
		$name = sanitize_text_field( $request->get_param( 'name' ) );

		if ( empty( $name ) ) {
			return new \WP_REST_Response( [
				'success' => false,
				'message' => __( 'Name darf nicht leer sein.', 'wptrustrocket' ),
			], 400 );
		}

		$id = WPTR_DB::create_group( $name );

		if ( ! $id ) {
			return new \WP_REST_Response( [
				'success' => false,
				'message' => __( 'Gruppe konnte nicht erstellt werden.', 'wptrustrocket' ),
			], 500 );
		}

		return new \WP_REST_Response( [
			'success' => true,
			'id'      => $id,
			'group'   => WPTR_DB::get_group( $id ),
		] );
	}

	public function delete_group( \WP_REST_Request $request ): \WP_REST_Response {
		$id = (int) $request->get_param( 'id' );
		WPTR_DB::delete_group( $id );
		return new \WP_REST_Response( [ 'success' => true ] );
	}

	public function save_group_reviews( \WP_REST_Request $request ): \WP_REST_Response {
		$id         = (int) $request->get_param( 'id' );
		$review_ids = $request->get_param( 'review_ids' );

		if ( ! is_array( $review_ids ) ) {
			$review_ids = [];
		}

		$review_ids = array_map( 'intval', $review_ids );
		WPTR_DB::set_group_reviews( $id, $review_ids );

		return new \WP_REST_Response( [
			'success' => true,
			'count'   => count( $review_ids ),
		] );
	}

	public function get_reviews( \WP_REST_Request $request ): \WP_REST_Response {
		$group = sanitize_text_field( $request->get_param( 'group' ) ?? '' );

		// Enforce hard limit on public endpoint to prevent data scraping.
		$requested_limit = (int) ( $request->get_param( 'count' ) ?: 0 );
		$max_limit       = 50;
		$limit           = ( $requested_limit > 0 && $requested_limit <= $max_limit ) ? $requested_limit : $max_limit;

		// Whitelist allowed orderby values.
		$allowed_orderby = [ 'submitted_at', 'rating', 'sort_order' ];
		$orderby         = sanitize_key( $request->get_param( 'orderby' ) ?: 'submitted_at' );
		if ( ! in_array( $orderby, $allowed_orderby, true ) ) {
			$orderby = 'submitted_at';
		}

		$args = [
			'limit'      => $limit,
			'min_rating' => (float) ( $request->get_param( 'min_rating' ) ?: 0 ),
			'orderby'    => $orderby,
			'order'      => strtoupper( sanitize_key( $request->get_param( 'order' ) ?: 'DESC' ) ) === 'ASC' ? 'ASC' : 'DESC',
		];

		// Public endpoint requires a group — don't expose all reviews without context.
		if ( empty( $group ) ) {
			return new \WP_REST_Response( [
				'success' => false,
				'message' => 'group parameter required',
			], 400 );
		}

		$reviews = WPTR_DB::get_group_reviews( $group, $args );

		return new \WP_REST_Response( [
			'success' => true,
			'reviews' => $reviews,
			'count'   => count( $reviews ),
		] );
	}
}

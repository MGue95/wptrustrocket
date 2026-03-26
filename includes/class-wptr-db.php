<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPTR_DB {

	/* ───── Table names ───── */

	public static function table_reviews(): string {
		global $wpdb;
		return $wpdb->prefix . 'wptr_reviews';
	}

	public static function table_groups(): string {
		global $wpdb;
		return $wpdb->prefix . 'wptr_groups';
	}

	public static function table_pivot(): string {
		global $wpdb;
		return $wpdb->prefix . 'wptr_group_reviews';
	}

	/**
	 * Check if all plugin tables exist.
	 */
	public static function tables_exist(): bool {
		global $wpdb;
		$result = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", self::table_reviews() ) );
		return ! empty( $result );
	}

	/* ───── Reviews ───── */

	public static function upsert_review( array $data ): int {
		global $wpdb;

		$provider = $data['provider'] ?? 'trustedshops';

		$existing = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM " . self::table_reviews() . " WHERE external_id = %s AND provider = %s",
			$data['external_id'],
			$provider
		) );

		$row = [
			'external_id' => $data['external_id'],
			'provider'    => $provider,
			'rating'      => $data['rating'],
			'title'       => $data['title'] ?? '',
			'comment'     => $data['comment'] ?? '',
			'author_name' => $data['author_name'] ?? '',
			'submitted_at' => $data['submitted_at'] ?? null,
			'synced_at'   => current_time( 'mysql' ),
		];

		if ( $existing ) {
			$wpdb->update( self::table_reviews(), $row, [ 'id' => $existing ] );
			return (int) $existing;
		}

		$wpdb->insert( self::table_reviews(), $row );
		return (int) $wpdb->insert_id;
	}

	public static function get_reviews( array $args = [] ): array {
		global $wpdb;

		if ( ! self::tables_exist() ) {
			return [];
		}

		$defaults = [
			'orderby'    => 'submitted_at',
			'order'      => 'DESC',
			'limit'      => 0,
			'offset'     => 0,
			'min_rating' => 0,
			'search'     => '',
			'provider'   => '',
		];
		$args = wp_parse_args( $args, $defaults );

		$where  = [ '1=1' ];
		$params = [];

		if ( $args['min_rating'] > 0 ) {
			$where[]  = 'rating >= %f';
			$params[] = $args['min_rating'];
		}

		if ( ! empty( $args['provider'] ) ) {
			$where[]  = 'provider = %s';
			$params[] = $args['provider'];
		}

		if ( ! empty( $args['search'] ) ) {
			$where[] = '(title LIKE %s OR comment LIKE %s OR author_name LIKE %s)';
			$like    = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			array_push( $params, $like, $like, $like );
		}

		$allowed = [ 'submitted_at', 'rating', 'author_name', 'synced_at', 'id' ];
		$orderby = in_array( $args['orderby'], $allowed, true ) ? $args['orderby'] : 'submitted_at';
		$order   = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

		$sql = "SELECT * FROM " . self::table_reviews() . " WHERE " . implode( ' AND ', $where ) . " ORDER BY {$orderby} {$order}";

		if ( $args['limit'] > 0 ) {
			$sql     .= " LIMIT %d OFFSET %d";
			$params[] = $args['limit'];
			$params[] = $args['offset'];
		}

		if ( ! empty( $params ) ) {
			$sql = $wpdb->prepare( $sql, ...$params );
		}

		return $wpdb->get_results( $sql, ARRAY_A ) ?: [];
	}

	public static function count_reviews( float $min_rating = 0, string $provider = '' ): int {
		global $wpdb;

		if ( ! self::tables_exist() ) {
			return 0;
		}

		$where  = [ '1=1' ];
		$params = [];

		if ( $min_rating > 0 ) {
			$where[]  = 'rating >= %f';
			$params[] = $min_rating;
		}
		if ( ! empty( $provider ) ) {
			$where[]  = 'provider = %s';
			$params[] = $provider;
		}

		$sql = "SELECT COUNT(*) FROM " . self::table_reviews() . " WHERE " . implode( ' AND ', $where );

		if ( ! empty( $params ) ) {
			return (int) $wpdb->get_var( $wpdb->prepare( $sql, ...$params ) );
		}

		return (int) $wpdb->get_var( $sql );
	}

	public static function average_rating( string $provider = '' ): float {
		global $wpdb;

		if ( ! self::tables_exist() ) {
			return 0;
		}

		if ( ! empty( $provider ) ) {
			$avg = $wpdb->get_var( $wpdb->prepare(
				"SELECT AVG(rating) FROM " . self::table_reviews() . " WHERE provider = %s",
				$provider
			) );
		} else {
			$avg = $wpdb->get_var( "SELECT AVG(rating) FROM " . self::table_reviews() );
		}

		return $avg ? round( (float) $avg, 1 ) : 0;
	}

	public static function rating_distribution(): array {
		global $wpdb;

		if ( ! self::tables_exist() ) {
			return [ 5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0 ];
		}

		$results = $wpdb->get_results(
			"SELECT FLOOR(rating) as star, COUNT(*) as cnt FROM " . self::table_reviews() . " GROUP BY FLOOR(rating) ORDER BY star DESC",
			ARRAY_A
		);

		$dist = [ 5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0 ];
		foreach ( $results as $row ) {
			$dist[ (int) $row['star'] ] = (int) $row['cnt'];
		}
		return $dist;
	}

	public static function get_review( int $id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM " . self::table_reviews() . " WHERE id = %d", $id
		), ARRAY_A );
		return $row ?: null;
	}

	public static function delete_review( int $id ): bool {
		global $wpdb;
		$wpdb->delete( self::table_pivot(), [ 'review_id' => $id ] );
		return (bool) $wpdb->delete( self::table_reviews(), [ 'id' => $id ] );
	}

	/* ───── Groups ───── */

	public static function create_group( string $name ): ?int {
		global $wpdb;

		$slug     = sanitize_title( $name );
		$original = $slug;
		$counter  = 2;

		while ( $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM " . self::table_groups() . " WHERE slug = %s", $slug
		) ) ) {
			$slug = $original . '-' . $counter++;
		}

		$ok = $wpdb->insert( self::table_groups(), [
			'name'       => $name,
			'slug'       => $slug,
			'created_at' => current_time( 'mysql' ),
		] );

		return $ok ? (int) $wpdb->insert_id : null;
	}

	public static function get_groups(): array {
		global $wpdb;

		if ( ! self::tables_exist() ) {
			return [];
		}

		return $wpdb->get_results(
			"SELECT g.*, COUNT(gr.review_id) as review_count
			 FROM " . self::table_groups() . " g
			 LEFT JOIN " . self::table_pivot() . " gr ON g.id = gr.group_id
			 GROUP BY g.id
			 ORDER BY g.name ASC",
			ARRAY_A
		) ?: [];
	}

	public static function get_group_by_slug( string $slug ): ?array {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM " . self::table_groups() . " WHERE slug = %s", $slug
		), ARRAY_A );
		return $row ?: null;
	}

	public static function get_group( int $id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM " . self::table_groups() . " WHERE id = %d", $id
		), ARRAY_A );
		return $row ?: null;
	}

	public static function delete_group( int $id ): bool {
		global $wpdb;
		$wpdb->delete( self::table_pivot(), [ 'group_id' => $id ] );
		return (bool) $wpdb->delete( self::table_groups(), [ 'id' => $id ] );
	}

	public static function update_group( int $id, string $name ): bool {
		global $wpdb;
		return (bool) $wpdb->update( self::table_groups(), [ 'name' => $name ], [ 'id' => $id ] );
	}

	public static function set_group_reviews( int $group_id, array $review_ids ): void {
		global $wpdb;

		$wpdb->delete( self::table_pivot(), [ 'group_id' => $group_id ] );

		foreach ( array_values( $review_ids ) as $sort => $review_id ) {
			$wpdb->insert( self::table_pivot(), [
				'group_id'   => $group_id,
				'review_id'  => (int) $review_id,
				'sort_order' => $sort,
			] );
		}
	}

	public static function get_group_review_ids( int $group_id ): array {
		global $wpdb;
		return $wpdb->get_col( $wpdb->prepare(
			"SELECT review_id FROM " . self::table_pivot() . " WHERE group_id = %d ORDER BY sort_order ASC",
			$group_id
		) ) ?: [];
	}

	public static function get_group_reviews( string $slug, array $args = [] ): array {
		global $wpdb;

		$group = self::get_group_by_slug( $slug );
		if ( ! $group ) {
			return [];
		}

		$defaults = [
			'orderby'    => 'sort_order',
			'order'      => 'ASC',
			'limit'      => 0,
			'min_rating' => 0,
		];
		$args = wp_parse_args( $args, $defaults );

		$where  = [ 'gr.group_id = %d' ];
		$params = [ (int) $group['id'] ];

		if ( $args['min_rating'] > 0 ) {
			$where[]  = 'r.rating >= %f';
			$params[] = $args['min_rating'];
		}

		$allowed = [ 'sort_order', 'submitted_at', 'rating', 'author_name' ];
		$ob      = in_array( $args['orderby'], $allowed, true ) ? $args['orderby'] : 'sort_order';
		$ob_col  = $ob === 'sort_order' ? 'gr.sort_order' : 'r.' . $ob;
		$order   = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

		$sql = "SELECT r.*, gr.sort_order
				FROM " . self::table_reviews() . " r
				INNER JOIN " . self::table_pivot() . " gr ON r.id = gr.review_id
				WHERE " . implode( ' AND ', $where ) . "
				ORDER BY {$ob_col} {$order}";

		if ( $args['limit'] > 0 ) {
			$sql     .= " LIMIT %d";
			$params[] = $args['limit'];
		}

		return $wpdb->get_results( $wpdb->prepare( $sql, ...$params ), ARRAY_A ) ?: [];
	}

	public static function get_group_stats( string $slug ): array {
		global $wpdb;

		$group = self::get_group_by_slug( $slug );
		if ( ! $group ) {
			return [ 'count' => 0, 'average' => 0 ];
		}

		$row = $wpdb->get_row( $wpdb->prepare(
			"SELECT COUNT(*) as cnt, AVG(r.rating) as avg_rating
			 FROM " . self::table_reviews() . " r
			 INNER JOIN " . self::table_pivot() . " gr ON r.id = gr.review_id
			 WHERE gr.group_id = %d",
			$group['id']
		), ARRAY_A );

		return [
			'count'   => (int) ( $row['cnt'] ?? 0 ),
			'average' => round( (float) ( $row['avg_rating'] ?? 0 ), 1 ),
		];
	}

	/* ───── Cleanup ───── */

	public static function drop_tables(): void {
		global $wpdb;
		$wpdb->query( "DROP TABLE IF EXISTS " . self::table_pivot() );
		$wpdb->query( "DROP TABLE IF EXISTS " . self::table_groups() );
		$wpdb->query( "DROP TABLE IF EXISTS " . self::table_reviews() );
	}
}

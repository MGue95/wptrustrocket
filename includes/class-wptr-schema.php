<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPTR_Schema {

	public function __construct() {
		add_action( 'wp_footer', [ $this, 'output_schema' ] );
	}

	/**
	 * Output JSON-LD AggregateRating when reviews are rendered on the page.
	 */
	public function output_schema(): void {
		if ( ! wp_style_is( 'wptr-frontend', 'enqueued' ) ) {
			return;
		}

		$total = WPTR_DB::count_reviews();
		if ( $total === 0 ) {
			return;
		}

		$avg = WPTR_DB::average_rating();

		$schema = [
			'@context'        => 'https://schema.org',
			'@type'           => 'Organization',
			'name'            => get_bloginfo( 'name' ),
			'url'             => home_url( '/' ),
			'aggregateRating' => [
				'@type'       => 'AggregateRating',
				'ratingValue' => (string) $avg,
				'bestRating'  => '5',
				'worstRating' => '1',
				'ratingCount' => (string) $total,
			],
		];

		echo "\n<script type=\"application/ld+json\">" . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "</script>\n";
	}
}

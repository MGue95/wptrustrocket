<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPTR_Shortcode {

	public function __construct() {
		add_shortcode( 'wptrustrocket', [ $this, 'render_reviews' ] );
		add_shortcode( 'wptrustrocket_badge', [ $this, 'render_badge' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'register_assets' ] );
	}

	public function register_assets(): void {
		wp_register_style( 'wptr-frontend', WPTR_PLUGIN_URL . 'assets/css/frontend.css', [], WPTR_VERSION );
		wp_register_script( 'wptr-frontend', WPTR_PLUGIN_URL . 'assets/js/frontend.js', [], WPTR_VERSION, true );
	}

	public function render_reviews( $atts ): string {
		$atts = shortcode_atts( [
			'group'       => '',
			'layout'      => 'grid',
			'count'       => 0,
			'min_rating'  => 0,
			'columns'     => 3,
			'orderby'     => 'sort_order',
			'order'       => 'asc',
			'autoplay'    => 0,
			'show_title'  => 'true',
			'show_date'   => 'true',
			'show_author' => 'true',
			'show_rating' => 'true',
			'class'       => '',
			// Styling
			'card_bg'           => '',
			'card_border'       => '',
			'card_radius'       => '',
			'card_padding'      => '',
			'card_shadow'       => '',
			'star_color'        => '',
			'star_size'         => '',
			'title_color'       => '',
			'title_size'        => '',
			'text_color'        => '',
			'text_size'         => '',
			'author_color'      => '',
			'date_color'        => '',
			'gap'               => '',
		], $atts, 'wptrustrocket' );

		if ( empty( $atts['group'] ) ) {
			return '<!-- WPTrustRocket: group parameter required -->';
		}

		$orderby = $atts['orderby'];
		if ( $orderby === 'date' ) {
			$orderby = 'submitted_at';
		}

		$reviews = WPTR_DB::get_group_reviews( $atts['group'], [
			'limit'      => (int) $atts['count'],
			'min_rating' => (float) $atts['min_rating'],
			'orderby'    => $orderby,
			'order'      => strtoupper( $atts['order'] ),
		] );

		if ( empty( $reviews ) ) {
			return '<!-- WPTrustRocket: no reviews found -->';
		}

		wp_enqueue_style( 'wptr-frontend' );
		if ( $atts['layout'] === 'slider' ) {
			wp_enqueue_script( 'wptr-frontend' );
		}

		$render_atts = $atts;
		foreach ( [ 'show_title', 'show_date', 'show_author', 'show_rating' ] as $key ) {
			$render_atts[ $key ] = filter_var( $render_atts[ $key ], FILTER_VALIDATE_BOOLEAN );
		}

		return WPTR_Renderer::render( $reviews, $render_atts );
	}

	public function render_badge( $atts ): string {
		$atts = shortcode_atts( [
			'group'        => '',
			'class'        => '',
			'card_bg'      => '',
			'card_border'  => '',
			'card_radius'  => '',
			'card_padding' => '',
			'star_color'   => '',
			'text_color'   => '',
		], $atts, 'wptrustrocket_badge' );

		if ( empty( $atts['group'] ) ) {
			return '<!-- WPTrustRocket: group parameter required -->';
		}

		wp_enqueue_style( 'wptr-frontend' );

		return WPTR_Renderer::render_badge( $atts['group'], $atts );
	}
}

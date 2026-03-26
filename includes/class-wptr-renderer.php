<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPTR_Renderer {

	/* ───── Stars (SVG) ───── */

	public static function render_stars( float $rating, string $size = '16' ): string {
		$full  = (int) floor( $rating );
		$half  = ( $rating - $full ) >= 0.25 ? 1 : 0;
		$empty = 5 - $full - $half;

		$html = '<span class="wptr-stars-wrap" aria-label="' . esc_attr( $rating . ' von 5 Sternen' ) . '">';

		for ( $i = 0; $i < $full; $i++ ) {
			$html .= self::star_svg( 'full', $size );
		}
		if ( $half ) {
			$html .= self::star_svg( 'half', $size );
		}
		for ( $i = 0; $i < $empty; $i++ ) {
			$html .= self::star_svg( 'empty', $size );
		}

		$html .= '</span>';
		return $html;
	}

	private static function star_svg( string $type, string $size ): string {
		$s    = intval( $size );
		$gold = 'var(--wptr-star-color, #F5A623)';
		$grey = '#D4D4D4';
		$path = 'M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z';

		$w = 'width="' . $s . '" height="' . $s . '"';

		if ( $type === 'full' ) {
			return '<svg class="wptr-star wptr-star--full" ' . $w . ' viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="' . $path . '" fill="' . $gold . '"/></svg>';
		}

		if ( $type === 'half' ) {
			$uid = 'wptr-clip-' . wp_unique_id();
			return '<svg class="wptr-star wptr-star--half" ' . $w . ' viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">'
				. '<defs><clipPath id="' . $uid . '"><rect x="0" y="0" width="12" height="24"/></clipPath></defs>'
				. '<path d="' . $path . '" fill="' . $grey . '"/>'
				. '<path d="' . $path . '" fill="' . $gold . '" clip-path="url(#' . $uid . ')"/></svg>';
		}

		return '<svg class="wptr-star wptr-star--empty" ' . $w . ' viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="' . $path . '" fill="' . $grey . '"/></svg>';
	}

	/* ───── Inline style from atts ───── */

	private static function build_style_vars( array $atts ): string {
		$map = [
			'card_bg'      => '--wptr-card-bg',
			'card_border'  => '--wptr-card-border',
			'card_radius'  => '--wptr-card-radius',
			'card_padding' => '--wptr-card-padding',
			'card_shadow'  => '--wptr-card-shadow',
			'star_color'   => '--wptr-star-color',
			'star_size'    => '--wptr-star-size',
			'title_color'  => '--wptr-title-color',
			'title_size'   => '--wptr-title-size',
			'text_color'   => '--wptr-text-color',
			'text_size'    => '--wptr-text-size',
			'author_color' => '--wptr-author-color',
			'date_color'   => '--wptr-date-color',
			'gap'          => '--wptr-gap',
		];

		$vars = [];
		foreach ( $map as $key => $var ) {
			if ( ! empty( $atts[ $key ] ) ) {
				$vars[] = esc_attr( $var ) . ':' . esc_attr( $atts[ $key ] );
			}
		}

		return ! empty( $vars ) ? implode( ';', $vars ) : '';
	}

	private static function style_attr( array $atts, string $extra = '' ): string {
		$vars = self::build_style_vars( $atts );
		$all  = trim( $vars . ( $extra ? ';' . $extra : '' ), ';' );
		return $all ? ' style="' . $all . '"' : '';
	}

	/* ───── Main render dispatcher ───── */

	public static function render( array $reviews, array $atts = [] ): string {
		if ( empty( $reviews ) ) {
			return '';
		}

		$defaults = [
			'layout'      => 'grid',
			'columns'     => 3,
			'autoplay'    => 0,
			'show_title'  => true,
			'show_date'   => true,
			'show_author' => true,
			'show_rating' => true,
			'class'       => '',
		];
		$atts = wp_parse_args( $atts, $defaults );

		switch ( $atts['layout'] ) {
			case 'slider':
				return self::render_slider( $reviews, $atts );
			case 'list':
				return self::render_list( $reviews, $atts );
			case 'badge':
				return self::render_badge_from_reviews( $reviews, $atts );
			default:
				return self::render_grid( $reviews, $atts );
		}
	}

	/* ───── Grid ───── */

	private static function render_grid( array $reviews, array $atts ): string {
		$cols  = max( 1, min( 6, (int) $atts['columns'] ) );
		$extra = ! empty( $atts['class'] ) ? ' ' . esc_attr( $atts['class'] ) : '';

		$style = self::style_attr( $atts, '--wptr-columns:' . $cols );

		$html = '<div class="wptr-reviews wptr-reviews--grid' . $extra . '"' . $style . '>';
		foreach ( $reviews as $review ) {
			$html .= self::render_card( $review, $atts );
		}
		$html .= '</div>';
		return $html;
	}

	/* ───── Slider ───── */

	private static function render_slider( array $reviews, array $atts ): string {
		$autoplay = (int) $atts['autoplay'];
		$extra    = ! empty( $atts['class'] ) ? ' ' . esc_attr( $atts['class'] ) : '';
		$uid      = 'wptr-slider-' . wp_unique_id();

		$style = self::style_attr( $atts );

		$html  = '<div class="wptr-reviews wptr-reviews--slider' . $extra . '" id="' . $uid . '" data-autoplay="' . $autoplay . '"' . $style . '>';
		$html .= '<div class="wptr-slider-track">';
		foreach ( $reviews as $review ) {
			$html .= '<div class="wptr-slider-slide">' . self::render_card( $review, $atts ) . '</div>';
		}
		$html .= '</div>';
		$html .= '<button class="wptr-slider-btn wptr-slider-btn--prev" aria-label="' . esc_attr__( 'Zurueck', 'wptrustrocket' ) . '">&lsaquo;</button>';
		$html .= '<button class="wptr-slider-btn wptr-slider-btn--next" aria-label="' . esc_attr__( 'Weiter', 'wptrustrocket' ) . '">&rsaquo;</button>';
		$html .= '<div class="wptr-slider-dots"></div>';
		$html .= '</div>';

		return $html;
	}

	/* ───── List ───── */

	private static function render_list( array $reviews, array $atts ): string {
		$extra = ! empty( $atts['class'] ) ? ' ' . esc_attr( $atts['class'] ) : '';
		$style = self::style_attr( $atts );

		$html = '<div class="wptr-reviews wptr-reviews--list' . $extra . '"' . $style . '>';
		foreach ( $reviews as $review ) {
			$html .= self::render_list_item( $review, $atts );
		}
		$html .= '</div>';
		return $html;
	}

	/* ───── Badge (from group slug) ───── */

	public static function render_badge( string $group_slug, array $atts = [] ): string {
		$stats = WPTR_DB::get_group_stats( $group_slug );
		if ( $stats['count'] === 0 ) {
			return '';
		}

		$extra = ! empty( $atts['class'] ) ? ' ' . esc_attr( $atts['class'] ) : '';
		$style = self::style_attr( $atts );

		$html  = '<div class="wptr-badge' . $extra . '"' . $style . '>';
		$html .= '<div class="wptr-badge-rating">' . esc_html( $stats['average'] ) . '</div>';
		$html .= '<div class="wptr-badge-stars">' . self::render_stars( $stats['average'], '22' ) . '</div>';
		$html .= '<div class="wptr-badge-count">' . sprintf(
			esc_html( _n( '%s Bewertung', '%s Bewertungen', $stats['count'], 'wptrustrocket' ) ),
			number_format_i18n( $stats['count'] )
		) . '</div>';
		$html .= '<div class="wptr-badge-source">Trusted Shops</div>';
		$html .= '</div>';

		return $html;
	}

	/* ───── Badge (from reviews array) ───── */

	private static function render_badge_from_reviews( array $reviews, array $atts ): string {
		$count = count( $reviews );
		if ( $count === 0 ) {
			return '';
		}

		$sum = array_sum( array_column( $reviews, 'rating' ) );
		$avg = round( $sum / $count, 1 );

		$extra = ! empty( $atts['class'] ) ? ' ' . esc_attr( $atts['class'] ) : '';
		$style = self::style_attr( $atts );

		$html  = '<div class="wptr-badge' . $extra . '"' . $style . '>';
		$html .= '<div class="wptr-badge-rating">' . esc_html( $avg ) . '</div>';
		$html .= '<div class="wptr-badge-stars">' . self::render_stars( $avg, '22' ) . '</div>';
		$html .= '<div class="wptr-badge-count">' . sprintf(
			esc_html( _n( '%s Bewertung', '%s Bewertungen', $count, 'wptrustrocket' ) ),
			number_format_i18n( $count )
		) . '</div>';
		$html .= '<div class="wptr-badge-source">Trusted Shops</div>';
		$html .= '</div>';

		return $html;
	}

	/* ───── Single Review Card ───── */

	private static function render_card( array $review, array $atts ): string {
		$html = '<div class="wptr-card">';

		if ( $atts['show_rating'] ) {
			$html .= '<div class="wptr-card-stars">' . self::render_stars( (float) $review['rating'] ) . '</div>';
		}

		if ( $atts['show_title'] && ! empty( $review['title'] ) ) {
			$html .= '<div class="wptr-card-title">' . esc_html( $review['title'] ) . '</div>';
		}

		if ( ! empty( $review['comment'] ) ) {
			$html .= '<div class="wptr-card-comment">' . esc_html( $review['comment'] ) . '</div>';
		}

		$parts = [];
		if ( $atts['show_author'] && ! empty( $review['author_name'] ) ) {
			$parts[] = '<span class="wptr-card-author">' . esc_html( $review['author_name'] ) . '</span>';
		}
		if ( $atts['show_date'] && ! empty( $review['submitted_at'] ) ) {
			$parts[] = '<span class="wptr-card-date">' . esc_html( date_i18n( 'd.m.Y', strtotime( $review['submitted_at'] ) ) ) . '</span>';
		}

		if ( ! empty( $parts ) ) {
			$html .= '<div class="wptr-card-footer">' . implode( '', $parts ) . '</div>';
		}

		$html .= '</div>';
		return $html;
	}

	/* ───── List Item ───── */

	private static function render_list_item( array $review, array $atts ): string {
		$html  = '<div class="wptr-list-item">';
		$html .= '<div class="wptr-list-item-left">';

		if ( $atts['show_rating'] ) {
			$html .= '<div class="wptr-card-stars">' . self::render_stars( (float) $review['rating'] ) . '</div>';
		}
		if ( $atts['show_author'] && ! empty( $review['author_name'] ) ) {
			$html .= '<span class="wptr-card-author">' . esc_html( $review['author_name'] ) . '</span>';
		}
		if ( $atts['show_date'] && ! empty( $review['submitted_at'] ) ) {
			$html .= '<span class="wptr-card-date">' . esc_html( date_i18n( 'd.m.Y', strtotime( $review['submitted_at'] ) ) ) . '</span>';
		}

		$html .= '</div><div class="wptr-list-item-right">';

		if ( $atts['show_title'] && ! empty( $review['title'] ) ) {
			$html .= '<div class="wptr-card-title">' . esc_html( $review['title'] ) . '</div>';
		}
		if ( ! empty( $review['comment'] ) ) {
			$html .= '<div class="wptr-card-comment">' . esc_html( $review['comment'] ) . '</div>';
		}

		$html .= '</div></div>';
		return $html;
	}
}

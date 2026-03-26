<?php
/**
 * WPTrustRocket — Oxygen Builder Integration.
 *
 * This file is only loaded when OxyEl is available.
 * The element classes are NOT prefixed with WPTR_ to avoid autoloader conflicts.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bootstrap function called from the main plugin file.
 */
function wptr_register_oxygen_elements() {
	// Register our section in Oxygen's Add (+) panel.
	add_action( 'oxygen_add_plus_sections', function () {
		if ( class_exists( 'CT_Toolbar' ) ) {
			CT_Toolbar::oxygen_add_plus_accordion_section( 'wptrustrocket', 'WPTrustRocket' );
		}
	} );

	// Populate the accordion section content (renders element buttons inside the section).
	// Hook name format: oxygen_add_plus_{$section_slug}_section_content
	add_action( 'oxygen_add_plus_wptrustrocket_section_content', function () {
		// Render all element buttons registered under the "wptrustrocket::elements" subsection.
		do_action( 'oxygen_add_plus_wptrustrocket_elements' );
	} );

	// Enqueue frontend assets inside the builder.
	add_action( 'wp_enqueue_scripts', function () {
		if ( defined( 'SHOW_CT_BUILDER' ) || isset( $_GET['ct_builder'] ) || isset( $_GET['oxygen_iframe'] ) ) {
			wp_enqueue_style( 'wptr-frontend', WPTR_PLUGIN_URL . 'assets/css/frontend.css', [], WPTR_VERSION );
			wp_enqueue_script( 'wptr-frontend', WPTR_PLUGIN_URL . 'assets/js/frontend.js', [], WPTR_VERSION, true );
		}
	} );

	// Instantiate elements — this triggers OxyEl registration.
	new WPTRocketReviewsElement();
	new WPTRocketBadgeElement();
}

/**
 * Helper: safely get groups for Oxygen controls (won't crash if table is missing).
 */
function wptr_oxy_get_group_options() {
	$options = [ '' => '-- Gruppe waehlen --' ];
	try {
		$groups = WPTR_DB::get_groups();
		foreach ( $groups as $g ) {
			$options[ $g['slug'] ] = $g['name'] . ' (' . $g['review_count'] . ')';
		}
	} catch ( \Exception $e ) {
		// Table might not exist yet.
	}
	return $options;
}

/* ═══════════════════════════════════════════════════════════
   REVIEWS ELEMENT
   ═══════════════════════════════════════════════════════════ */

class WPTRocketReviewsElement extends OxyEl {

	function init() {}

	function afterInit() {
		$this->removeApplyParamsButton();
	}

	function name() {
		return 'TrustRocket Reviews';
	}

	function slug() {
		return 'wptrustrocket_reviews';
	}

	function icon() {
		return '';
	}

	function button_place() {
		return 'wptrustrocket::elements';
	}

	function button_priority() {
		return 1;
	}

	function controls() {
		$gc = $this->addOptionControl( [
			'type'    => 'dropdown',
			'name'    => 'Gruppe',
			'slug'    => 'wptr_group',
			'default' => '',
		] );
		$gc->setValue( wptr_oxy_get_group_options() );
		$gc->rebuildElementOnChange();

		$lc = $this->addOptionControl( [
			'type'    => 'dropdown',
			'name'    => 'Layout',
			'slug'    => 'wptr_layout',
			'default' => 'grid',
		] );
		$lc->setValue( [ 'grid' => 'Grid', 'slider' => 'Slider', 'list' => 'Liste' ] );
		$lc->rebuildElementOnChange();

		$cc = $this->addOptionControl( [
			'type'    => 'dropdown',
			'name'    => 'Spalten (Grid)',
			'slug'    => 'wptr_columns',
			'default' => '3',
		] );
		$cc->setValue( [ '1' => '1', '2' => '2', '3' => '3', '4' => '4' ] );
		$cc->rebuildElementOnChange();

		$this->addOptionControl( [ 'type' => 'textfield', 'name' => 'Max. Anzahl (0=alle)', 'slug' => 'wptr_count', 'default' => '0' ] );

		$mr = $this->addOptionControl( [ 'type' => 'dropdown', 'name' => 'Min. Bewertung', 'slug' => 'wptr_min_rating', 'default' => '0' ] );
		$mr->setValue( [ '0' => 'Alle', '3' => '3+', '4' => '4+', '5' => '5' ] );

		$ob = $this->addOptionControl( [ 'type' => 'dropdown', 'name' => 'Sortierung', 'slug' => 'wptr_orderby', 'default' => 'sort_order' ] );
		$ob->setValue( [ 'sort_order' => 'Manuell', 'submitted_at' => 'Datum', 'rating' => 'Bewertung' ] );

		$od = $this->addOptionControl( [ 'type' => 'dropdown', 'name' => 'Richtung', 'slug' => 'wptr_order', 'default' => 'ASC' ] );
		$od->setValue( [ 'ASC' => 'Aufsteigend', 'DESC' => 'Absteigend' ] );

		$this->addOptionControl( [ 'type' => 'textfield', 'name' => 'Autoplay ms (0=aus)', 'slug' => 'wptr_autoplay', 'default' => '0' ] );

		$st = $this->addOptionControl( [ 'type' => 'dropdown', 'name' => 'Titel', 'slug' => 'wptr_show_title', 'default' => 'true' ] );
		$st->setValue( [ 'true' => 'Anzeigen', 'false' => 'Verstecken' ] );

		$sd = $this->addOptionControl( [ 'type' => 'dropdown', 'name' => 'Datum', 'slug' => 'wptr_show_date', 'default' => 'true' ] );
		$sd->setValue( [ 'true' => 'Anzeigen', 'false' => 'Verstecken' ] );

		$sa = $this->addOptionControl( [ 'type' => 'dropdown', 'name' => 'Autor', 'slug' => 'wptr_show_author', 'default' => 'true' ] );
		$sa->setValue( [ 'true' => 'Anzeigen', 'false' => 'Verstecken' ] );
	}

	function render( $options, $defaults, $content ) {
		wp_enqueue_style( 'wptr-frontend', WPTR_PLUGIN_URL . 'assets/css/frontend.css', [], WPTR_VERSION );

		$group = isset( $options['wptr_group'] ) ? $options['wptr_group'] : '';
		if ( empty( $group ) ) {
			echo '<div style="padding:40px;text-align:center;background:#f8fafc;border:2px dashed #e2e8f0;border-radius:12px;color:#64748b;">Bitte eine Bewertungs-Gruppe waehlen.</div>';
			return;
		}

		$layout = isset( $options['wptr_layout'] ) ? $options['wptr_layout'] : 'grid';
		if ( $layout === 'slider' ) {
			wp_enqueue_script( 'wptr-frontend', WPTR_PLUGIN_URL . 'assets/js/frontend.js', [], WPTR_VERSION, true );
		}

		$reviews = WPTR_DB::get_group_reviews( $group, [
			'limit'      => (int) ( isset( $options['wptr_count'] ) ? $options['wptr_count'] : 0 ),
			'min_rating' => (float) ( isset( $options['wptr_min_rating'] ) ? $options['wptr_min_rating'] : 0 ),
			'orderby'    => isset( $options['wptr_orderby'] ) ? $options['wptr_orderby'] : 'sort_order',
			'order'      => isset( $options['wptr_order'] ) ? $options['wptr_order'] : 'ASC',
		] );

		if ( empty( $reviews ) ) {
			echo '<div style="padding:40px;text-align:center;background:#f8fafc;border:2px dashed #e2e8f0;border-radius:12px;color:#64748b;">Keine Bewertungen in dieser Gruppe.</div>';
			return;
		}

		echo WPTR_Renderer::render( $reviews, [
			'layout'      => $layout,
			'columns'     => (int) ( isset( $options['wptr_columns'] ) ? $options['wptr_columns'] : 3 ),
			'autoplay'    => (int) ( isset( $options['wptr_autoplay'] ) ? $options['wptr_autoplay'] : 0 ),
			'show_title'  => ( isset( $options['wptr_show_title'] ) ? $options['wptr_show_title'] : 'true' ) === 'true',
			'show_date'   => ( isset( $options['wptr_show_date'] ) ? $options['wptr_show_date'] : 'true' ) === 'true',
			'show_author' => ( isset( $options['wptr_show_author'] ) ? $options['wptr_show_author'] : 'true' ) === 'true',
			'show_rating' => true,
		] );
	}

	function customCSS( $options, $isInEditor ) {
		return '';
	}

	function enablePresets() {
		return true;
	}

	function enableFullPresets() {
		return true;
	}
}

/* ═══════════════════════════════════════════════════════════
   BADGE ELEMENT
   ═══════════════════════════════════════════════════════════ */

class WPTRocketBadgeElement extends OxyEl {

	function init() {}

	function afterInit() {
		$this->removeApplyParamsButton();
	}

	function name() {
		return 'TrustRocket Badge';
	}

	function slug() {
		return 'wptrustrocket_badge';
	}

	function icon() {
		return '';
	}

	function button_place() {
		return 'wptrustrocket::elements';
	}

	function button_priority() {
		return 2;
	}

	function controls() {
		$gc = $this->addOptionControl( [
			'type'    => 'dropdown',
			'name'    => 'Gruppe',
			'slug'    => 'wptr_group',
			'default' => '',
		] );
		$gc->setValue( wptr_oxy_get_group_options() );
		$gc->rebuildElementOnChange();
	}

	function render( $options, $defaults, $content ) {
		wp_enqueue_style( 'wptr-frontend', WPTR_PLUGIN_URL . 'assets/css/frontend.css', [], WPTR_VERSION );

		$group = isset( $options['wptr_group'] ) ? $options['wptr_group'] : '';
		if ( empty( $group ) ) {
			echo '<div style="padding:40px;text-align:center;background:#f8fafc;border:2px dashed #e2e8f0;border-radius:12px;color:#64748b;">Bitte eine Bewertungs-Gruppe waehlen.</div>';
			return;
		}

		echo WPTR_Renderer::render_badge( $group );
	}

	function enablePresets() {
		return true;
	}

	function enableFullPresets() {
		return true;
	}
}

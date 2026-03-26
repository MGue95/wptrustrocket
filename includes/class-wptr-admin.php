<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPTR_Admin {

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'register_menus' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

	public function register_menus(): void {
		add_menu_page(
			'WPTrustRocket',
			'TrustRocket',
			'manage_options',
			'wptrustrocket',
			[ $this, 'render_dashboard' ],
			'dashicons-star-filled',
			80
		);

		add_submenu_page( 'wptrustrocket', __( 'Dashboard', 'wptrustrocket' ), __( 'Dashboard', 'wptrustrocket' ), 'manage_options', 'wptrustrocket', [ $this, 'render_dashboard' ] );
		add_submenu_page( 'wptrustrocket', __( 'Bewertungen', 'wptrustrocket' ), __( 'Bewertungen', 'wptrustrocket' ), 'manage_options', 'wptrustrocket-reviews', [ $this, 'render_reviews' ] );
		add_submenu_page( 'wptrustrocket', __( 'Gruppen', 'wptrustrocket' ), __( 'Gruppen', 'wptrustrocket' ), 'manage_options', 'wptrustrocket-groups', [ $this, 'render_groups' ] );
		add_submenu_page( 'wptrustrocket', __( 'Einstellungen', 'wptrustrocket' ), __( 'Einstellungen', 'wptrustrocket' ), 'manage_options', 'wptrustrocket-settings', [ $this, 'render_settings' ] );
	}

	public function enqueue_assets( string $hook ): void {
		if ( strpos( $hook, 'wptrustrocket' ) === false ) {
			return;
		}

		wp_enqueue_style( 'wptr-admin', WPTR_PLUGIN_URL . 'assets/css/admin.css', [], WPTR_VERSION );
		wp_enqueue_script( 'wptr-admin', WPTR_PLUGIN_URL . 'assets/js/admin.js', [], WPTR_VERSION, true );

		wp_localize_script( 'wptr-admin', 'wptrAdmin', [
			'restUrl' => rest_url( 'wptrustrocket/v1/' ),
			'nonce'   => wp_create_nonce( 'wp_rest' ),
			'i18n'    => [
				'confirmDelete' => __( 'Wirklich loeschen?', 'wptrustrocket' ),
				'syncing'       => __( 'Synchronisiere...', 'wptrustrocket' ),
				'syncDone'      => __( 'Synchronisierung abgeschlossen!', 'wptrustrocket' ),
				'syncError'     => __( 'Fehler bei der Synchronisierung.', 'wptrustrocket' ),
				'saved'         => __( 'Gespeichert!', 'wptrustrocket' ),
				'error'         => __( 'Ein Fehler ist aufgetreten.', 'wptrustrocket' ),
				'selected'      => __( 'ausgewaehlt', 'wptrustrocket' ),
				'testOk'        => __( 'Verbindung erfolgreich!', 'wptrustrocket' ),
				'testFail'      => __( 'Verbindung fehlgeschlagen.', 'wptrustrocket' ),
			],
		] );
	}

	/* ================================================================
	 *  DASHBOARD
	 * ============================================================= */

	public function render_dashboard(): void {
		$total     = WPTR_DB::count_reviews();
		$avg       = WPTR_DB::average_rating();
		$dist      = WPTR_DB::rating_distribution();
		$groups    = WPTR_DB::get_groups();
		$last_sync = get_option( 'wptr_last_sync', '' );
		$next_sync = WPTR_Cron::get_next_sync();
		?>
		<div class="wrap wptr-wrap">
			<h1 class="wptr-page-title">
				<span class="wptr-logo">&#9733;</span> WPTrustRocket Dashboard
			</h1>

			<div class="wptr-stats-grid">
				<div class="wptr-stat-card">
					<div class="wptr-stat-value"><?php echo esc_html( $total ); ?></div>
					<div class="wptr-stat-label"><?php esc_html_e( 'Bewertungen', 'wptrustrocket' ); ?></div>
				</div>
				<div class="wptr-stat-card">
					<div class="wptr-stat-value"><?php echo esc_html( $avg ); ?> <small>/ 5</small></div>
					<div class="wptr-stat-label"><?php esc_html_e( 'Durchschnitt', 'wptrustrocket' ); ?></div>
				</div>
				<div class="wptr-stat-card">
					<div class="wptr-stat-value"><?php echo (int) count( $groups ); ?></div>
					<div class="wptr-stat-label"><?php esc_html_e( 'Gruppen', 'wptrustrocket' ); ?></div>
				</div>
				<div class="wptr-stat-card">
					<div class="wptr-stat-value wptr-stat-value--small"><?php echo $last_sync ? esc_html( date_i18n( 'd.m.Y H:i', strtotime( $last_sync ) ) ) : '&ndash;'; ?></div>
					<div class="wptr-stat-label"><?php esc_html_e( 'Letzte Sync', 'wptrustrocket' ); ?></div>
				</div>
			</div>

			<!-- Rating Distribution -->
			<div class="wptr-card">
				<h2 class="wptr-card-title"><?php esc_html_e( 'Bewertungsverteilung', 'wptrustrocket' ); ?></h2>
				<div class="wptr-distribution">
					<?php for ( $star = 5; $star >= 1; $star -- ) :
						$count = $dist[ $star ];
						$pct   = $total > 0 ? round( $count / $total * 100 ) : 0;
						?>
						<div class="wptr-dist-row">
							<span class="wptr-dist-label"><?php echo (int) $star; ?> &#9733;</span>
							<div class="wptr-dist-bar-wrap">
								<div class="wptr-dist-bar" style="width:<?php echo (int) $pct; ?>%"></div>
							</div>
							<span class="wptr-dist-count"><?php echo (int) $count; ?></span>
						</div>
					<?php endfor; ?>
				</div>
			</div>

			<!-- Actions -->
			<div class="wptr-card">
				<h2 class="wptr-card-title"><?php esc_html_e( 'Aktionen', 'wptrustrocket' ); ?></h2>
				<div class="wptr-actions-row">
					<button type="button" class="button button-primary" id="wptr-sync-btn">
						<?php esc_html_e( 'Jetzt synchronisieren', 'wptrustrocket' ); ?>
					</button>
					<?php if ( $next_sync ) : ?>
						<span class="wptr-next-sync">
							<?php printf( esc_html__( 'Naechste Auto-Sync: %s', 'wptrustrocket' ), esc_html( date_i18n( 'd.m.Y H:i', strtotime( $next_sync ) ) ) ); ?>
						</span>
					<?php endif; ?>
				</div>
			</div>

			<!-- Shortcode Help -->
			<div class="wptr-card">
				<h2 class="wptr-card-title"><?php esc_html_e( 'Verwendung', 'wptrustrocket' ); ?></h2>
				<div class="wptr-help-grid">
					<div class="wptr-help-item">
						<h3><?php esc_html_e( 'Shortcode – Reviews', 'wptrustrocket' ); ?></h3>
						<code>[wptrustrocket group="slug" layout="grid" count="6" min_rating="4" columns="3" orderby="date" order="desc"]</code>
					</div>
					<div class="wptr-help-item">
						<h3><?php esc_html_e( 'Shortcode – Badge', 'wptrustrocket' ); ?></h3>
						<code>[wptrustrocket_badge group="slug"]</code>
					</div>
					<div class="wptr-help-item">
						<h3><?php esc_html_e( 'Layouts', 'wptrustrocket' ); ?></h3>
						<p><code>grid</code> · <code>slider</code> · <code>list</code> · <code>badge</code></p>
					</div>
					<div class="wptr-help-item">
						<h3>Oxygen Builder</h3>
						<p><?php esc_html_e( 'Im Editor unter „WPTrustRocket" findest du die Elemente mit allen Einstellungen.', 'wptrustrocket' ); ?></p>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/* ================================================================
	 *  BEWERTUNGEN
	 * ============================================================= */

	public function render_reviews(): void {
		$search   = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
		$paged    = max( 1, (int) ( $_GET['paged'] ?? 1 ) );
		$per_page = 50;

		$total       = WPTR_DB::count_reviews();
		$reviews     = WPTR_DB::get_reviews( [
			'search' => $search,
			'limit'  => $per_page,
			'offset' => ( $paged - 1 ) * $per_page,
		] );
		$total_pages = (int) ceil( $total / $per_page );
		?>
		<div class="wrap wptr-wrap">
			<h1 class="wptr-page-title">
				<?php esc_html_e( 'Bewertungen', 'wptrustrocket' ); ?>
				<span class="wptr-count">(<?php echo esc_html( $total ); ?>)</span>
			</h1>

			<div class="wptr-toolbar">
				<form method="get" class="wptr-search-form">
					<input type="hidden" name="page" value="wptrustrocket-reviews">
					<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Suchen...', 'wptrustrocket' ); ?>" class="wptr-search-input">
					<button type="submit" class="button"><?php esc_html_e( 'Suchen', 'wptrustrocket' ); ?></button>
				</form>
				<button type="button" class="button button-primary" id="wptr-sync-btn">
					<?php esc_html_e( 'Synchronisieren', 'wptrustrocket' ); ?>
				</button>
			</div>

			<?php if ( empty( $reviews ) && empty( $search ) ) : ?>
				<div class="wptr-empty-state">
					<p><?php esc_html_e( 'Noch keine Bewertungen. Konfiguriere die API-Zugangsdaten und synchronisiere.', 'wptrustrocket' ); ?></p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wptrustrocket-settings' ) ); ?>" class="button button-primary">
						<?php esc_html_e( 'Einstellungen', 'wptrustrocket' ); ?>
					</a>
				</div>
			<?php elseif ( empty( $reviews ) ) : ?>
				<div class="wptr-empty-state">
					<p><?php printf( esc_html__( 'Keine Ergebnisse fuer „%s".', 'wptrustrocket' ), esc_html( $search ) ); ?></p>
				</div>
			<?php else : ?>
				<div class="wptr-reviews-grid" id="wptr-reviews-grid">
					<?php foreach ( $reviews as $review ) : ?>
						<div class="wptr-review-card" data-id="<?php echo esc_attr( $review['id'] ); ?>">
							<div class="wptr-review-card-header">
								<span class="wptr-stars"><?php echo WPTR_Renderer::render_stars( (float) $review['rating'] ); ?></span>
								<span class="wptr-rating-num"><?php echo esc_html( $review['rating'] ); ?>/5</span>
							</div>
							<?php if ( ! empty( $review['title'] ) ) : ?>
								<div class="wptr-review-card-title"><?php echo esc_html( $review['title'] ); ?></div>
							<?php endif; ?>
							<?php if ( ! empty( $review['comment'] ) ) : ?>
								<div class="wptr-review-card-comment"><?php echo esc_html( wp_trim_words( $review['comment'], 30 ) ); ?></div>
							<?php endif; ?>
							<div class="wptr-review-card-footer">
								<span class="wptr-review-author"><?php echo esc_html( $review['author_name'] ?: '&ndash;' ); ?></span>
								<span class="wptr-review-date"><?php echo $review['submitted_at'] ? esc_html( date_i18n( 'd.m.Y', strtotime( $review['submitted_at'] ) ) ) : '&ndash;'; ?></span>
							</div>
						</div>
					<?php endforeach; ?>
				</div>

				<?php if ( $total_pages > 1 ) : ?>
					<div class="wptr-pagination">
						<?php
						echo wp_kses_post( paginate_links( [
							'base'      => add_query_arg( 'paged', '%#%' ),
							'format'    => '',
							'current'   => $paged,
							'total'     => $total_pages,
							'prev_text' => '&lsaquo;',
							'next_text' => '&rsaquo;',
						] ) );
						?>
					</div>
				<?php endif; ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/* ================================================================
	 *  GRUPPEN
	 * ============================================================= */

	public function render_groups(): void {
		$groups       = WPTR_DB::get_groups();
		$editing_id   = isset( $_GET['edit'] ) ? (int) $_GET['edit'] : 0;
		$editing_group = $editing_id ? WPTR_DB::get_group( $editing_id ) : null;
		?>
		<div class="wrap wptr-wrap">
			<h1 class="wptr-page-title"><?php esc_html_e( 'Gruppen verwalten', 'wptrustrocket' ); ?></h1>

			<div class="wptr-groups-layout">
				<!-- Sidebar -->
				<div class="wptr-groups-sidebar">
					<div class="wptr-card">
						<h2 class="wptr-card-title"><?php esc_html_e( 'Neue Gruppe', 'wptrustrocket' ); ?></h2>
						<div class="wptr-create-group">
							<input type="text" id="wptr-new-group-name" placeholder="<?php esc_attr_e( 'Gruppenname...', 'wptrustrocket' ); ?>" class="regular-text">
							<button type="button" class="button button-primary" id="wptr-create-group-btn">
								<?php esc_html_e( 'Erstellen', 'wptrustrocket' ); ?>
							</button>
						</div>
					</div>

					<div class="wptr-card">
						<h2 class="wptr-card-title"><?php esc_html_e( 'Gruppen', 'wptrustrocket' ); ?></h2>
						<?php if ( empty( $groups ) ) : ?>
							<p class="wptr-muted"><?php esc_html_e( 'Noch keine Gruppen angelegt.', 'wptrustrocket' ); ?></p>
						<?php else : ?>
							<div class="wptr-groups-list" id="wptr-groups-list">
								<?php foreach ( $groups as $group ) : ?>
									<div class="wptr-group-item <?php echo $editing_id === (int) $group['id'] ? 'wptr-group-item--active' : ''; ?>" data-id="<?php echo esc_attr( $group['id'] ); ?>">
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=wptrustrocket-groups&edit=' . $group['id'] ) ); ?>" class="wptr-group-link">
											<strong><?php echo esc_html( $group['name'] ); ?></strong>
											<span class="wptr-group-meta"><?php echo esc_html( $group['slug'] ); ?> &middot; <?php echo esc_html( $group['review_count'] ); ?> <?php esc_html_e( 'Bewertungen', 'wptrustrocket' ); ?></span>
										</a>
										<button type="button" class="wptr-group-delete" data-id="<?php echo esc_attr( $group['id'] ); ?>" title="<?php esc_attr_e( 'Loeschen', 'wptrustrocket' ); ?>">&times;</button>
									</div>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>
				</div>

				<!-- Editor -->
				<div class="wptr-groups-editor">
					<?php if ( $editing_group ) : ?>
						<?php $this->render_group_editor( $editing_group ); ?>
					<?php else : ?>
						<div class="wptr-card wptr-empty-editor">
							<p><?php esc_html_e( 'Waehle eine Gruppe aus der Liste oder erstelle eine neue.', 'wptrustrocket' ); ?></p>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
	}

	private function render_group_editor( array $group ): void {
		$group_id     = (int) $group['id'];
		$selected_ids = WPTR_DB::get_group_review_ids( $group_id );
		$all_reviews  = WPTR_DB::get_reviews( [ 'limit' => 2000 ] );
		?>
		<div class="wptr-card">
			<div class="wptr-group-editor-header">
				<h2 class="wptr-card-title"><?php echo esc_html( $group['name'] ); ?></h2>
				<div class="wptr-group-shortcode">
					<code>[wptrustrocket group="<?php echo esc_attr( $group['slug'] ); ?>"]</code>
				</div>
			</div>

			<div class="wptr-group-toolbar">
				<div class="wptr-group-search">
					<input type="search" id="wptr-group-search" placeholder="<?php esc_attr_e( 'Bewertungen filtern...', 'wptrustrocket' ); ?>">
				</div>
				<div class="wptr-group-actions">
					<span id="wptr-selected-count"><?php echo count( $selected_ids ); ?></span> <?php esc_html_e( 'ausgewaehlt', 'wptrustrocket' ); ?>
					<button type="button" class="button" id="wptr-select-all"><?php esc_html_e( 'Alle', 'wptrustrocket' ); ?></button>
					<button type="button" class="button" id="wptr-select-none"><?php esc_html_e( 'Keine', 'wptrustrocket' ); ?></button>
					<button type="button" class="button button-primary" id="wptr-save-group" data-group-id="<?php echo $group_id; ?>">
						<?php esc_html_e( 'Speichern', 'wptrustrocket' ); ?>
					</button>
				</div>
			</div>

			<div class="wptr-selectable-reviews" id="wptr-selectable-reviews">
				<?php foreach ( $all_reviews as $review ) :
					$is_selected = in_array( $review['id'], $selected_ids );
					?>
					<div class="wptr-sel-review <?php echo $is_selected ? 'wptr-sel-review--selected' : ''; ?>"
						 data-review-id="<?php echo esc_attr( $review['id'] ); ?>"
						 data-search="<?php echo esc_attr( mb_strtolower( $review['title'] . ' ' . $review['comment'] . ' ' . $review['author_name'] ) ); ?>">
						<label class="wptr-sel-review-label">
							<input type="checkbox" class="wptr-review-checkbox" value="<?php echo esc_attr( $review['id'] ); ?>" <?php checked( $is_selected ); ?>>
							<div class="wptr-sel-review-content">
								<div class="wptr-sel-review-top">
									<span class="wptr-stars wptr-stars--small"><?php echo WPTR_Renderer::render_stars( (float) $review['rating'] ); ?></span>
									<span class="wptr-sel-review-date"><?php echo $review['submitted_at'] ? esc_html( date_i18n( 'd.m.Y', strtotime( $review['submitted_at'] ) ) ) : ''; ?></span>
								</div>
								<?php if ( ! empty( $review['title'] ) ) : ?>
									<div class="wptr-sel-review-title"><?php echo esc_html( $review['title'] ); ?></div>
								<?php endif; ?>
								<?php if ( ! empty( $review['comment'] ) ) : ?>
									<div class="wptr-sel-review-comment"><?php echo esc_html( wp_trim_words( $review['comment'], 20 ) ); ?></div>
								<?php endif; ?>
								<div class="wptr-sel-review-author"><?php echo esc_html( $review['author_name'] ?: '&ndash;' ); ?></div>
							</div>
						</label>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	/* ================================================================
	 *  EINSTELLUNGEN
	 * ============================================================= */

	public function render_settings(): void {
		if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['wptr_settings_nonce'] ) ) {
			if ( wp_verify_nonce( $_POST['wptr_settings_nonce'], 'wptr_save_settings' ) && current_user_can( 'manage_options' ) ) {
				update_option( 'wptr_client_id', sanitize_text_field( $_POST['wptr_client_id'] ?? '' ) );
				// Only update secret if a new one was entered.
				$new_secret = sanitize_text_field( $_POST['wptr_client_secret'] ?? '' );
				if ( ! empty( $new_secret ) ) {
					update_option( 'wptr_client_secret', $new_secret );
				}
				update_option( 'wptr_tsid', sanitize_text_field( $_POST['wptr_tsid'] ?? '' ) );

				$allowed_intervals = [ 'hourly', 'twicedaily', 'daily', 'weekly' ];
				$new_interval      = sanitize_key( $_POST['wptr_sync_interval'] ?? 'twicedaily' );
				if ( ! in_array( $new_interval, $allowed_intervals, true ) ) {
					$new_interval = 'twicedaily';
				}
				$old_interval = get_option( 'wptr_sync_interval', 'twicedaily' );
				update_option( 'wptr_sync_interval', $new_interval );

				if ( $new_interval !== $old_interval ) {
					WPTR_Cron::reschedule();
				}

				echo '<div class="notice notice-success"><p>' . esc_html__( 'Einstellungen gespeichert.', 'wptrustrocket' ) . '</p></div>';
			}
		}

		$client_id     = get_option( 'wptr_client_id', '' );
		$client_secret = get_option( 'wptr_client_secret', '' );
		$tsid          = get_option( 'wptr_tsid', '' );
		$sync_interval = get_option( 'wptr_sync_interval', 'twicedaily' );
		?>
		<div class="wrap wptr-wrap">
			<h1 class="wptr-page-title"><?php esc_html_e( 'Einstellungen', 'wptrustrocket' ); ?></h1>

			<form method="post" class="wptr-settings-form">
				<?php wp_nonce_field( 'wptr_save_settings', 'wptr_settings_nonce' ); ?>

				<div class="wptr-card">
					<h2 class="wptr-card-title"><?php esc_html_e( 'Trusted Shops API', 'wptrustrocket' ); ?></h2>
					<table class="form-table">
						<tr>
							<th><label for="wptr_tsid"><?php esc_html_e( 'TSID (Channel ID)', 'wptrustrocket' ); ?></label></th>
							<td>
								<input type="text" id="wptr_tsid" name="wptr_tsid" value="<?php echo esc_attr( $tsid ); ?>" class="regular-text">
								<p class="description"><?php esc_html_e( 'Trusted Shops ID / Channel Reference.', 'wptrustrocket' ); ?></p>
							</td>
						</tr>
						<tr>
							<th><label for="wptr_client_id"><?php esc_html_e( 'Client ID', 'wptrustrocket' ); ?></label></th>
							<td><input type="text" id="wptr_client_id" name="wptr_client_id" value="<?php echo esc_attr( $client_id ); ?>" class="regular-text"></td>
						</tr>
						<tr>
							<th><label for="wptr_client_secret"><?php esc_html_e( 'Client Secret', 'wptrustrocket' ); ?></label></th>
							<td>
								<input type="password" id="wptr_client_secret" name="wptr_client_secret" value="" class="regular-text" placeholder="<?php echo ! empty( $client_secret ) ? '********' : ''; ?>">
								<p class="description"><?php esc_html_e( 'Nur ausfuellen, wenn du das Secret aendern moechtest.', 'wptrustrocket' ); ?></p>
							</td>
						</tr>
					</table>
					<div class="wptr-settings-actions">
						<button type="button" class="button" id="wptr-test-connection"><?php esc_html_e( 'Verbindung testen', 'wptrustrocket' ); ?></button>
						<span id="wptr-connection-status"></span>
					</div>
				</div>

				<div class="wptr-card">
					<h2 class="wptr-card-title"><?php esc_html_e( 'Synchronisierung', 'wptrustrocket' ); ?></h2>
					<table class="form-table">
						<tr>
							<th><label for="wptr_sync_interval"><?php esc_html_e( 'Intervall', 'wptrustrocket' ); ?></label></th>
							<td>
								<select id="wptr_sync_interval" name="wptr_sync_interval">
									<option value="hourly" <?php selected( $sync_interval, 'hourly' ); ?>><?php esc_html_e( 'Stuendlich', 'wptrustrocket' ); ?></option>
									<option value="twicedaily" <?php selected( $sync_interval, 'twicedaily' ); ?>><?php esc_html_e( 'Zweimal taeglich', 'wptrustrocket' ); ?></option>
									<option value="daily" <?php selected( $sync_interval, 'daily' ); ?>><?php esc_html_e( 'Taeglich', 'wptrustrocket' ); ?></option>
									<option value="weekly" <?php selected( $sync_interval, 'weekly' ); ?>><?php esc_html_e( 'Woechentlich', 'wptrustrocket' ); ?></option>
								</select>
							</td>
						</tr>
					</table>
				</div>

				<p class="submit">
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Einstellungen speichern', 'wptrustrocket' ); ?></button>
				</p>
			</form>
		</div>
		<?php
	}
}

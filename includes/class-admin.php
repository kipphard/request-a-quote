<?php
/**
 * WordPress-Admin-UI: Menü, Einstellungsseite und POST-Handler.
 *
 * @package Kipphard\Angebotsanfrage
 */

namespace Kipphard\Angebotsanfrage;

defined( 'ABSPATH' ) || exit;

/**
 * Registriert Admin-Menü und verarbeitet Formulareinsendungen.
 */
class Admin {

	/**
	 * Hooks registrieren.
	 */
	public function hooks() {
		add_action( 'admin_menu', array( $this, 'register_menus' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_post_aga_save_settings', array( $this, 'handle_save_settings' ) );
	}

	/**
	 * Untermenü unter WooCommerce registrieren.
	 */
	public function register_menus() {
		add_submenu_page(
			'woocommerce',
			__( 'Angebotsanfrage – Einstellungen', 'angebotsanfrage' ),
			__( 'Angebotsanfrage', 'angebotsanfrage' ),
			Helpers::CAP,
			AGA_SLUG,
			array( $this, 'render_settings' )
		);
	}

	/**
	 * Assets nur auf der Plugin-Einstellungsseite einbinden.
	 *
	 * @param string $hook Aktueller Admin-Seiten-Hook.
	 */
	public function enqueue_assets( $hook ) {
		if ( 'woocommerce_page_' . AGA_SLUG !== $hook ) {
			return;
		}
		wp_enqueue_style(
			'aga-admin',
			AGA_URL . 'assets/admin.css',
			array(),
			AGA_VERSION
		);
		wp_enqueue_script(
			'aga-admin',
			AGA_URL . 'assets/admin.js',
			array(),
			AGA_VERSION,
			true
		);
	}

	// -------------------------------------------------------------------------
	// POST-Handler
	// -------------------------------------------------------------------------

	/**
	 * Einstellungen speichern.
	 */
	public function handle_save_settings() {
		Helpers::guard_post( 'aga_save_settings' );

		$clean = Helpers::sanitize_settings( $_POST );
		update_option( Helpers::OPT_SETTINGS, $clean );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'   => AGA_SLUG,
					'notice' => 'saved',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	// -------------------------------------------------------------------------
	// Seiten-Renderer
	// -------------------------------------------------------------------------

	/**
	 * Einstellungsseite rendern.
	 */
	public function render_settings() {
		if ( ! current_user_can( Helpers::CAP ) ) {
			return;
		}

		$notice   = isset( $_GET['notice'] ) ? sanitize_key( $_GET['notice'] ) : '';
		$is_pro   = Helpers::is_pro();
		$settings = (array) get_option( Helpers::OPT_SETTINGS, array() );
		$defaults = Helpers::defaults();

		// Aktuelle Werte mit Fallback auf Standardwerte.
		$button_label    = isset( $settings['button_label'] ) ? $settings['button_label'] : $defaults['button_label'];
		$mode            = isset( $settings['mode'] ) ? $settings['mode'] : $defaults['mode'];
		$apply_to        = isset( $settings['apply_to'] ) ? $settings['apply_to'] : $defaults['apply_to'];
		$categories      = isset( $settings['categories'] ) ? (array) $settings['categories'] : array();
		$hide_price      = ! empty( $settings['hide_price'] );
		$recipient_email = isset( $settings['recipient_email'] ) ? $settings['recipient_email'] : $defaults['recipient_email'];
		$field_name      = isset( $settings['field_name'] ) ? (bool) $settings['field_name'] : true;
		$field_company   = isset( $settings['field_company'] ) ? (bool) $settings['field_company'] : true;
		$field_phone     = isset( $settings['field_phone'] ) ? (bool) $settings['field_phone'] : false;
		$field_message   = isset( $settings['field_message'] ) ? (bool) $settings['field_message'] : true;

		$all_cats = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
			)
		);
		?>
		<div class="wrap aga-wrap">
			<h1><?php esc_html_e( 'Angebotsanfrage – Einstellungen', 'angebotsanfrage' ); ?></h1>

			<?php if ( 'saved' === $notice ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Einstellungen gespeichert.', 'angebotsanfrage' ); ?></p>
				</div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="aga_save_settings">
				<?php wp_nonce_field( 'aga_save_settings' ); ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">
							<label for="aga-button-label"><?php esc_html_e( 'Button-Beschriftung', 'angebotsanfrage' ); ?></label>
						</th>
						<td>
							<input type="text" id="aga-button-label" name="button_label" class="regular-text"
								value="<?php echo esc_attr( $button_label ); ?>">
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Modus', 'angebotsanfrage' ); ?></th>
						<td>
							<label>
								<input type="radio" name="mode" value="add"
									<?php checked( $mode, 'add' ); ?>>
								<?php esc_html_e( 'Neben Warenkorb-Button anzeigen', 'angebotsanfrage' ); ?>
							</label><br>
							<label>
								<input type="radio" name="mode" value="replace"
									<?php checked( $mode, 'replace' ); ?>>
								<?php esc_html_e( 'Warenkorb-Button ersetzen', 'angebotsanfrage' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Anwenden auf', 'angebotsanfrage' ); ?></th>
						<td>
							<label>
								<input type="radio" name="apply_to" value="all" id="aga-apply-all"
									<?php checked( $apply_to, 'all' ); ?>>
								<?php esc_html_e( 'Alle Produkte', 'angebotsanfrage' ); ?>
							</label><br>
							<label>
								<input type="radio" name="apply_to" value="categories" id="aga-apply-cats"
									<?php checked( $apply_to, 'categories' ); ?>>
								<?php esc_html_e( 'Nur bestimmte Kategorien', 'angebotsanfrage' ); ?>
							</label>
							<div id="aga-categories-row" style="margin-top:8px;<?php echo ( 'categories' !== $apply_to ) ? 'display:none;' : ''; ?>">
								<?php if ( ! is_wp_error( $all_cats ) && ! empty( $all_cats ) ) : ?>
									<select name="categories[]" multiple size="6" style="min-width:240px;">
										<?php foreach ( $all_cats as $cat ) : ?>
											<option value="<?php echo esc_attr( $cat->term_id ); ?>"
												<?php echo in_array( $cat->term_id, $categories, true ) ? 'selected' : ''; ?>>
												<?php echo esc_html( $cat->name ); ?>
											</option>
										<?php endforeach; ?>
									</select>
									<p class="description"><?php esc_html_e( 'Strg/Cmd gedrückt halten für Mehrfachauswahl.', 'angebotsanfrage' ); ?></p>
								<?php else : ?>
									<p class="description"><?php esc_html_e( 'Keine Produktkategorien gefunden.', 'angebotsanfrage' ); ?></p>
								<?php endif; ?>
							</div>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Preise ausblenden', 'angebotsanfrage' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="hide_price" value="1"
									<?php checked( $hide_price ); ?>>
								<?php esc_html_e( 'Produktpreise auf Shop-Seiten ausblenden (CSS)', 'angebotsanfrage' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="aga-recipient-email"><?php esc_html_e( 'Empfänger-E-Mail', 'angebotsanfrage' ); ?></label>
						</th>
						<td>
							<input type="email" id="aga-recipient-email" name="recipient_email" class="regular-text"
								value="<?php echo esc_attr( $recipient_email ); ?>">
							<p class="description"><?php esc_html_e( 'An diese Adresse werden neue Anfragen gesendet.', 'angebotsanfrage' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Formularfelder', 'angebotsanfrage' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="field_name" value="1" <?php checked( $field_name ); ?>>
								<?php esc_html_e( 'Name (Pflichtfeld)', 'angebotsanfrage' ); ?>
							</label><br>
							<label>
								<input type="checkbox" name="field_company" value="1" <?php checked( $field_company ); ?>>
								<?php esc_html_e( 'Unternehmen', 'angebotsanfrage' ); ?>
							</label><br>
							<label>
								<input type="checkbox" name="field_phone" value="1" <?php checked( $field_phone ); ?>>
								<?php esc_html_e( 'Telefon', 'angebotsanfrage' ); ?>
							</label><br>
							<label>
								<input type="checkbox" name="field_message" value="1" <?php checked( $field_message ); ?>>
								<?php esc_html_e( 'Nachricht', 'angebotsanfrage' ); ?>
							</label>
						</td>
					</tr>
				</table>

				<?php submit_button( __( 'Einstellungen speichern', 'angebotsanfrage' ) ); ?>
			</form>

			<?php if ( $is_pro ) : ?>

				<hr>
				<div class="card aga-pro-settings" style="max-width:680px;padding:20px 24px;margin-top:20px;">
					<h2><?php esc_html_e( 'Pro-Funktionen', 'angebotsanfrage' ); ?></h2>
					<p><?php esc_html_e( 'Angebotsanfrage Pro ist aktiv.', 'angebotsanfrage' ); ?></p>
					<ul>
						<li><?php esc_html_e( 'Angebot → WooCommerce-Bestellung konvertieren (direkt im Admin)', 'angebotsanfrage' ); ?></li>
						<li><?php esc_html_e( 'PDF-Angebotsdokument generieren', 'angebotsanfrage' ); ?></li>
						<li><?php esc_html_e( 'Benutzerdefinierte Formularfelder', 'angebotsanfrage' ); ?></li>
					</ul>
				</div>

			<?php else : ?>

				<hr>
				<div class="card aga-pro-teaser" style="max-width:680px;padding:20px 24px;margin-top:20px;background:#f6f7f7;border:1px dashed #a7aaad;">
					<h2><?php esc_html_e( 'Angebotsanfrage Pro', 'angebotsanfrage' ); ?></h2>
					<ul class="aga-pro-features">
						<li>
							<span class="dashicons dashicons-cart"></span>
							<?php esc_html_e( 'Angebot direkt in eine WooCommerce-Bestellung umwandeln', 'angebotsanfrage' ); ?>
						</li>
						<li>
							<span class="dashicons dashicons-pdf"></span>
							<?php esc_html_e( 'PDF-Angebotsdokument für Kunden generieren', 'angebotsanfrage' ); ?>
						</li>
						<li>
							<span class="dashicons dashicons-editor-ul"></span>
							<?php esc_html_e( 'Eigene Formularfelder ergänzen', 'angebotsanfrage' ); ?>
						</li>
					</ul>
					<p>
						<a href="https://products.kipphard.com/angebotsanfrage" target="_blank" rel="noopener noreferrer" class="button button-secondary">
							<?php esc_html_e( 'Jetzt upgraden', 'angebotsanfrage' ); ?>
						</a>
					</p>
				</div>

			<?php endif; ?>

		</div>
		<?php
	}
}

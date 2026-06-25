<?php
/**
 * Frontend-Hooks: Button, Shortcode, Formular-Submit.
 *
 * @package Kipphard\Angebotsanfrage
 */

namespace Kipphard\Angebotsanfrage;

defined( 'ABSPATH' ) || exit;

/**
 * Registriert alle Frontend-Hooks und rendert Angebotsliste + Formular.
 */
class Frontend {

	/**
	 * Hooks registrieren.
	 */
	public function hooks() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_shortcode( 'angebotsanfrage', array( $this, 'render_shortcode' ) );
		add_action( 'admin_post_aga_submit_quote', array( $this, 'handle_submit' ) );
		add_action( 'admin_post_nopriv_aga_submit_quote', array( $this, 'handle_submit' ) );

		// Button auf Produktseiten.
		add_action( 'woocommerce_after_add_to_cart_button', array( $this, 'render_product_button' ) );

		// Button in der Shop-/Archiv-Ansicht.
		add_filter( 'woocommerce_loop_add_to_cart_link', array( $this, 'filter_loop_button' ), 10, 2 );
	}

	/**
	 * Assets einbinden.
	 */
	public function enqueue_assets() {
		wp_enqueue_style(
			'aga-frontend',
			AGA_URL . 'assets/frontend.css',
			array(),
			AGA_VERSION
		);
		wp_enqueue_script(
			'aga-frontend',
			AGA_URL . 'assets/frontend.js',
			array(),
			AGA_VERSION,
			true
		);
		wp_localize_script(
			'aga-frontend',
			'agaData',
			array(
				'ajaxUrl' => esc_url_raw( admin_url( 'admin-ajax.php' ) ),
				'nonce'   => wp_create_nonce( 'aga_quote_action' ),
				'i18n'    => array(
					'added'   => __( 'Zur Angebotsliste hinzugefügt.', 'angebotsanfrage' ),
					'removed' => __( 'Entfernt.', 'angebotsanfrage' ),
					'error'   => __( 'Fehler. Bitte Seite neu laden.', 'angebotsanfrage' ),
				),
			)
		);
	}

	// -------------------------------------------------------------------------
	// Button-Rendering
	// -------------------------------------------------------------------------

	/**
	 * Gibt den „Angebot anfragen"-Button auf der Produktseite aus.
	 */
	public function render_product_button() {
		global $product;
		if ( ! $product ) {
			return;
		}
		if ( ! $this->applies_to_product( $product ) ) {
			return;
		}
		$label = esc_html( Helpers::get( 'button_label' ) );
		$id    = absint( $product->get_id() );
		echo '<button type="button" class="button aga-add-to-quote" data-product-id="' . esc_attr( $id ) . '" data-qty="1">'
			. $label
			. '</button>';
	}

	/**
	 * Hängt den „Angebot anfragen"-Button im Shop-Loop an oder ersetzt den Warenkorb-Button.
	 *
	 * @param string      $html    Original-Button-HTML.
	 * @param \WC_Product $product Aktuelles Produkt.
	 * @return string
	 */
	public function filter_loop_button( $html, $product ) {
		if ( ! $this->applies_to_product( $product ) ) {
			return $html;
		}
		$label      = esc_html( Helpers::get( 'button_label' ) );
		$id         = absint( $product->get_id() );
		$quote_btn  = '<button type="button" class="button aga-add-to-quote" data-product-id="' . esc_attr( $id ) . '" data-qty="1">'
			. $label
			. '</button>';

		if ( 'replace' === Helpers::get( 'mode' ) ) {
			return $quote_btn;
		}
		return $html . ' ' . $quote_btn;
	}

	/**
	 * Prüft ob der Button für ein bestimmtes Produkt angezeigt werden soll.
	 *
	 * @param \WC_Product $product Produkt.
	 * @return bool
	 */
	private function applies_to_product( $product ) {
		$apply_to = Helpers::get( 'apply_to' );
		if ( 'all' === $apply_to ) {
			return true;
		}
		// Nur bestimmte Kategorien.
		$allowed_cats = (array) Helpers::get( 'categories' );
		if ( empty( $allowed_cats ) ) {
			return false;
		}
		$product_cats = wc_get_product_term_ids( $product->get_id(), 'product_cat' );
		return (bool) array_intersect( $allowed_cats, $product_cats );
	}

	// -------------------------------------------------------------------------
	// Shortcode
	// -------------------------------------------------------------------------

	/**
	 * Rendert den [angebotsanfrage]-Shortcode: Angebotsliste + Formular.
	 *
	 * @return string HTML-Ausgabe.
	 */
	public function render_shortcode() {
		$quote_list = new Quote_List();
		$items      = $quote_list->items();
		$notice     = isset( $_GET['aga_notice'] ) ? sanitize_key( $_GET['aga_notice'] ) : '';

		ob_start();

		if ( 'sent' === $notice ) {
			?>
			<div class="aga-notice aga-notice-success">
				<p><?php esc_html_e( 'Ihre Anfrage wurde erfolgreich abgeschickt. Wir melden uns in Kürze.', 'angebotsanfrage' ); ?></p>
			</div>
			<?php
			return ob_get_clean();
		}

		if ( 'error' === $notice ) {
			?>
			<div class="aga-notice aga-notice-error">
				<p><?php esc_html_e( 'Beim Absenden ist ein Fehler aufgetreten. Bitte versuchen Sie es erneut.', 'angebotsanfrage' ); ?></p>
			</div>
			<?php
		}

		?>
		<div class="aga-quote-wrap">

			<div class="aga-quote-list" id="aga-quote-list">
				<h3><?php esc_html_e( 'Ihre Angebotsliste', 'angebotsanfrage' ); ?></h3>

				<?php if ( empty( $items ) ) : ?>
					<p class="aga-empty"><?php esc_html_e( 'Ihre Angebotsliste ist leer. Fügen Sie Produkte über den „Angebot anfragen"-Button hinzu.', 'angebotsanfrage' ); ?></p>
				<?php else : ?>
					<table class="aga-list-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Produkt', 'angebotsanfrage' ); ?></th>
								<th><?php esc_html_e( 'Menge', 'angebotsanfrage' ); ?></th>
								<th></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $items as $item ) : ?>
								<?php
								$product_id = isset( $item['product_id'] ) ? absint( $item['product_id'] ) : 0;
								$qty        = isset( $item['qty'] ) ? absint( $item['qty'] ) : 1;
								$product    = $product_id ? wc_get_product( $product_id ) : null;
								if ( ! $product ) {
									continue;
								}
								?>
								<tr>
									<td>
										<a href="<?php echo esc_url( get_permalink( $product_id ) ); ?>">
											<?php echo esc_html( $product->get_name() ); ?>
										</a>
									</td>
									<td><?php echo esc_html( $qty ); ?></td>
									<td>
										<button type="button"
											class="aga-remove-from-quote"
											data-product-id="<?php echo esc_attr( $product_id ); ?>"
											aria-label="<?php echo esc_attr( sprintf( __( '%s entfernen', 'angebotsanfrage' ), $product->get_name() ) ); ?>">
											&times;
										</button>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>

			<?php if ( ! empty( $items ) ) : ?>
			<div class="aga-quote-form">
				<h3><?php esc_html_e( 'Anfrage absenden', 'angebotsanfrage' ); ?></h3>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="aga-form">
					<input type="hidden" name="action" value="aga_submit_quote">
					<?php wp_nonce_field( 'aga_submit_quote', '_aga_nonce' ); ?>

					<?php if ( Helpers::get( 'field_name' ) ) : ?>
					<div class="aga-field">
						<label for="aga-name">
							<?php esc_html_e( 'Name', 'angebotsanfrage' ); ?>
							<span class="aga-required" aria-hidden="true">*</span>
						</label>
						<input type="text" id="aga-name" name="aga_name" required autocomplete="name"
							value="<?php echo esc_attr( is_user_logged_in() ? wp_get_current_user()->display_name : '' ); ?>">
					</div>
					<?php endif; ?>

					<div class="aga-field">
						<label for="aga-email">
							<?php esc_html_e( 'E-Mail', 'angebotsanfrage' ); ?>
							<span class="aga-required" aria-hidden="true">*</span>
						</label>
						<input type="email" id="aga-email" name="aga_email" required autocomplete="email"
							value="<?php echo esc_attr( is_user_logged_in() ? wp_get_current_user()->user_email : '' ); ?>">
					</div>

					<?php if ( Helpers::get( 'field_company' ) ) : ?>
					<div class="aga-field">
						<label for="aga-company"><?php esc_html_e( 'Unternehmen', 'angebotsanfrage' ); ?></label>
						<input type="text" id="aga-company" name="aga_company" autocomplete="organization">
					</div>
					<?php endif; ?>

					<?php if ( Helpers::get( 'field_phone' ) ) : ?>
					<div class="aga-field">
						<label for="aga-phone"><?php esc_html_e( 'Telefon', 'angebotsanfrage' ); ?></label>
						<input type="tel" id="aga-phone" name="aga_phone" autocomplete="tel">
					</div>
					<?php endif; ?>

					<?php if ( Helpers::get( 'field_message' ) ) : ?>
					<div class="aga-field">
						<label for="aga-message"><?php esc_html_e( 'Nachricht', 'angebotsanfrage' ); ?></label>
						<textarea id="aga-message" name="aga_message" rows="4"></textarea>
					</div>
					<?php endif; ?>

					<p class="aga-required-note">
						<span class="aga-required" aria-hidden="true">*</span>
						<?php esc_html_e( 'Pflichtfeld', 'angebotsanfrage' ); ?>
					</p>

					<button type="submit" class="button aga-submit">
						<?php esc_html_e( 'Anfrage absenden', 'angebotsanfrage' ); ?>
					</button>
				</form>
			</div>
			<?php endif; ?>

		</div>
		<?php

		return ob_get_clean();
	}

	// -------------------------------------------------------------------------
	// Formular-Verarbeitung
	// -------------------------------------------------------------------------

	/**
	 * Verarbeitet den Formular-Submit der Angebotsanfrage.
	 * Öffentlich zugänglich → strenge Nonce- und Eingabevalidierung.
	 */
	public function handle_submit() {
		// Nonce prüfen (kein Capability-Check nötig – öffentliches Formular).
		if ( ! isset( $_POST['_aga_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_aga_nonce'] ) ), 'aga_submit_quote' ) ) {
			$this->redirect_form( 'error' );
		}

		// Eingaben sanitisieren.
		$name    = isset( $_POST['aga_name'] ) ? sanitize_text_field( wp_unslash( $_POST['aga_name'] ) ) : '';
		$email   = isset( $_POST['aga_email'] ) ? sanitize_email( wp_unslash( $_POST['aga_email'] ) ) : '';
		$company = isset( $_POST['aga_company'] ) ? sanitize_text_field( wp_unslash( $_POST['aga_company'] ) ) : '';
		$phone   = isset( $_POST['aga_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['aga_phone'] ) ) : '';
		$message = isset( $_POST['aga_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['aga_message'] ) ) : '';

		// Pflichtfelder prüfen.
		if ( empty( $email ) || ! is_email( $email ) ) {
			$this->redirect_form( 'error' );
		}

		if ( Helpers::get( 'field_name' ) && empty( $name ) ) {
			$this->redirect_form( 'error' );
		}

		// Angebotsliste aus der Session holen.
		$quote_list = new Quote_List();
		$items      = $quote_list->items();

		if ( empty( $items ) ) {
			$this->redirect_form( 'error' );
		}

		// Produkt-IDs validieren: nur veröffentlichte Produkte speichern.
		$clean_items = array();
		foreach ( $items as $item ) {
			$product_id = absint( isset( $item['product_id'] ) ? $item['product_id'] : 0 );
			$qty        = absint( isset( $item['qty'] ) ? $item['qty'] : 1 );
			$post       = $product_id ? get_post( $product_id ) : null;
			if ( $post && 'product' === $post->post_type && 'publish' === $post->post_status ) {
				$clean_items[] = array(
					'product_id' => $product_id,
					'qty'        => max( 1, $qty ),
				);
			}
		}

		if ( empty( $clean_items ) ) {
			$this->redirect_form( 'error' );
		}

		// CPT-Eintrag anlegen.
		$title   = $name ? $name : $email;
		$post_id = wp_insert_post(
			array(
				'post_type'   => 'aga_quote',
				'post_title'  => sanitize_text_field( $title ),
				'post_status' => 'publish',
			)
		);

		if ( ! $post_id || is_wp_error( $post_id ) ) {
			$this->redirect_form( 'error' );
		}

		update_post_meta( $post_id, '_aga_name', $name );
		update_post_meta( $post_id, '_aga_email', $email );
		update_post_meta( $post_id, '_aga_company', $company );
		update_post_meta( $post_id, '_aga_phone', $phone );
		update_post_meta( $post_id, '_aga_message', $message );
		update_post_meta( $post_id, '_aga_items', $clean_items );

		// E-Mails versenden.
		$this->send_admin_notification( $post_id, $name, $email, $company, $phone, $message, $clean_items );
		$this->send_customer_confirmation( $email, $name, $clean_items );

		// Angebotsliste leeren.
		$quote_list->clear();

		$this->redirect_form( 'sent' );
	}

	/**
	 * Admin-Benachrichtigungs-E-Mail senden.
	 *
	 * @param int    $post_id      CPT-ID.
	 * @param string $name         Kundenname.
	 * @param string $email        Kunden-E-Mail.
	 * @param string $company      Unternehmen.
	 * @param string $phone        Telefon.
	 * @param string $message      Nachricht.
	 * @param array  $items        Angefragte Produkte.
	 */
	private function send_admin_notification( $post_id, $name, $email, $company, $phone, $message, array $items ) {
		$recipient = Helpers::get( 'recipient_email' );
		$subject   = sprintf(
			/* translators: %s: Kundenname oder E-Mail */
			__( 'Neue Angebotsanfrage von %s', 'angebotsanfrage' ),
			$name ? $name : $email
		);

		$body  = __( 'Eine neue Angebotsanfrage ist eingegangen:', 'angebotsanfrage' ) . "\n\n";
		if ( $name ) {
			$body .= __( 'Name: ', 'angebotsanfrage' ) . $name . "\n";
		}
		$body .= __( 'E-Mail: ', 'angebotsanfrage' ) . $email . "\n";
		if ( $company ) {
			$body .= __( 'Unternehmen: ', 'angebotsanfrage' ) . $company . "\n";
		}
		if ( $phone ) {
			$body .= __( 'Telefon: ', 'angebotsanfrage' ) . $phone . "\n";
		}
		if ( $message ) {
			$body .= "\n" . __( 'Nachricht:', 'angebotsanfrage' ) . "\n" . $message . "\n";
		}

		$body .= "\n" . __( 'Angefragte Produkte:', 'angebotsanfrage' ) . "\n";
		foreach ( $items as $item ) {
			$product = wc_get_product( $item['product_id'] );
			$name_p  = $product ? $product->get_name() : sprintf( '#%d', $item['product_id'] );
			$body   .= '- ' . $name_p . ' × ' . (int) $item['qty'] . "\n";
		}

		$body .= "\n" . sprintf(
			/* translators: %s: URL der Anfrage im Admin */
			__( 'Anfrage im Admin: %s', 'angebotsanfrage' ),
			admin_url( 'post.php?post=' . $post_id . '&action=edit' )
		);

		wp_mail( $recipient, $subject, $body );
	}

	/**
	 * Bestätigungs-E-Mail an den Kunden senden.
	 *
	 * @param string $email  Kunden-E-Mail.
	 * @param string $name   Kundenname.
	 * @param array  $items  Angefragte Produkte.
	 */
	private function send_customer_confirmation( $email, $name, array $items ) {
		$site    = get_bloginfo( 'name' );
		$subject = sprintf(
			/* translators: %s: Shop-Name */
			__( 'Ihre Angebotsanfrage bei %s', 'angebotsanfrage' ),
			$site
		);

		$greeting = $name
			? sprintf( __( 'Hallo %s,', 'angebotsanfrage' ), $name )
			: __( 'Hallo,', 'angebotsanfrage' );

		$body  = $greeting . "\n\n";
		$body .= __( 'vielen Dank für Ihre Angebotsanfrage. Wir haben folgende Produkte erhalten:', 'angebotsanfrage' ) . "\n\n";

		foreach ( $items as $item ) {
			$product = wc_get_product( $item['product_id'] );
			$name_p  = $product ? $product->get_name() : sprintf( '#%d', $item['product_id'] );
			$body   .= '- ' . $name_p . ' × ' . (int) $item['qty'] . "\n";
		}

		$body .= "\n" . __( 'Wir werden uns so schnell wie möglich bei Ihnen melden.', 'angebotsanfrage' ) . "\n\n";
		$body .= sprintf( __( 'Mit freundlichen Grüßen,\n%s', 'angebotsanfrage' ), $site );

		wp_mail( $email, $subject, $body );
	}

	/**
	 * Leitet mit einem Notice-Parameter zurück zum Referrer (oder zur Startseite).
	 *
	 * @param string $notice Notice-Schlüssel.
	 */
	private function redirect_form( $notice ) {
		$referer = wp_get_referer();
		$url     = $referer ? $referer : home_url( '/' );
		$url     = add_query_arg( 'aga_notice', sanitize_key( $notice ), $url );
		wp_safe_redirect( $url );
		exit;
	}
}

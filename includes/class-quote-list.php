<?php
/**
 * Session-basierte Angebotsliste mit AJAX-Endpunkten.
 *
 * @package Kipphard\Angebotsanfrage
 */

namespace Kipphard\Angebotsanfrage;

defined( 'ABSPATH' ) || exit;

/**
 * Verwaltet die Angebotsliste im WooCommerce-Session-Speicher.
 */
class Quote_List {

	/** Session-Schlüssel für die Angebotsliste. */
	const SESSION_KEY = 'aga_quote_list';

	/**
	 * Hooks registrieren.
	 */
	public function hooks() {
		add_action( 'wp_ajax_aga_add_to_quote', array( $this, 'ajax_add' ) );
		add_action( 'wp_ajax_nopriv_aga_add_to_quote', array( $this, 'ajax_add' ) );
		add_action( 'wp_ajax_aga_remove_from_quote', array( $this, 'ajax_remove' ) );
		add_action( 'wp_ajax_nopriv_aga_remove_from_quote', array( $this, 'ajax_remove' ) );
	}

	// -------------------------------------------------------------------------
	// Session-Zugriff
	// -------------------------------------------------------------------------

	/**
	 * Liefert die aktuelle Angebotsliste aus der Session.
	 *
	 * @return array<int,array{product_id:int,qty:int}>
	 */
	public function items() {
		if ( ! WC()->session ) {
			return array();
		}
		$items = WC()->session->get( self::SESSION_KEY );
		return is_array( $items ) ? $items : array();
	}

	/**
	 * Anzahl der Positionen in der Angebotsliste.
	 *
	 * @return int
	 */
	public function count() {
		return count( $this->items() );
	}

	/**
	 * Fügt ein Produkt zur Liste hinzu oder erhöht die Menge.
	 *
	 * @param int $product_id Produkt-ID (bereits validiert).
	 * @param int $qty        Menge (bereits validiert, >= 1).
	 * @return bool True bei Erfolg.
	 */
	public function add( $product_id, $qty ) {
		if ( ! WC()->session ) {
			return false;
		}
		$items = $this->items();
		foreach ( $items as &$item ) {
			if ( (int) $item['product_id'] === $product_id ) {
				$item['qty'] = absint( $item['qty'] ) + $qty;
				WC()->session->set( self::SESSION_KEY, $items );
				return true;
			}
		}
		unset( $item );
		$items[] = array(
			'product_id' => $product_id,
			'qty'        => $qty,
		);
		WC()->session->set( self::SESSION_KEY, $items );
		return true;
	}

	/**
	 * Entfernt ein Produkt vollständig aus der Liste.
	 *
	 * @param int $product_id Produkt-ID.
	 * @return bool True wenn ein Eintrag entfernt wurde.
	 */
	public function remove( $product_id ) {
		if ( ! WC()->session ) {
			return false;
		}
		$items   = $this->items();
		$before  = count( $items );
		$items   = array_values(
			array_filter(
				$items,
				static function ( $item ) use ( $product_id ) {
					return (int) $item['product_id'] !== $product_id;
				}
			)
		);
		WC()->session->set( self::SESSION_KEY, $items );
		return count( $items ) < $before;
	}

	/**
	 * Leert die gesamte Angebotsliste.
	 */
	public function clear() {
		if ( WC()->session ) {
			WC()->session->set( self::SESSION_KEY, array() );
		}
	}

	// -------------------------------------------------------------------------
	// AJAX-Handler (öffentlich zugänglich – strenge Validierung)
	// -------------------------------------------------------------------------

	/**
	 * AJAX: Produkt zur Angebotsliste hinzufügen.
	 * Öffentlich → Nonce + Produktvalidierung zwingend.
	 */
	public function ajax_add() {
		check_ajax_referer( 'aga_quote_action', 'nonce' );

		$product_id = absint( isset( $_POST['product_id'] ) ? $_POST['product_id'] : 0 );
		$qty        = absint( isset( $_POST['qty'] ) ? $_POST['qty'] : 1 );

		if ( $qty < 1 ) {
			$qty = 1;
		}

		if ( ! $this->is_valid_product( $product_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Ungültiges Produkt.', 'angebotsanfrage' ) ), 400 );
		}

		$this->add( $product_id, $qty );

		wp_send_json_success(
			array(
				'count'   => $this->count(),
				'message' => __( 'Zur Angebotsliste hinzugefügt.', 'angebotsanfrage' ),
			)
		);
	}

	/**
	 * AJAX: Produkt aus der Angebotsliste entfernen.
	 * Öffentlich → Nonce zwingend.
	 */
	public function ajax_remove() {
		check_ajax_referer( 'aga_quote_action', 'nonce' );

		$product_id = absint( isset( $_POST['product_id'] ) ? $_POST['product_id'] : 0 );

		if ( ! $this->is_valid_product( $product_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Ungültiges Produkt.', 'angebotsanfrage' ) ), 400 );
		}

		$this->remove( $product_id );

		wp_send_json_success(
			array(
				'count'   => $this->count(),
				'message' => __( 'Aus der Angebotsliste entfernt.', 'angebotsanfrage' ),
			)
		);
	}

	// -------------------------------------------------------------------------
	// Interne Hilfsmethoden
	// -------------------------------------------------------------------------

	/**
	 * Prüft ob eine Produkt-ID auf ein veröffentlichtes WooCommerce-Produkt zeigt.
	 *
	 * @param int $product_id Zu prüfende ID.
	 * @return bool
	 */
	private function is_valid_product( $product_id ) {
		if ( $product_id <= 0 ) {
			return false;
		}
		$post = get_post( $product_id );
		return $post && 'product' === $post->post_type && 'publish' === $post->post_status;
	}
}

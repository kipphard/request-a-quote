<?php
/**
 * Gemeinsame Hilfsmethoden: Rechte, Optionen, Sanitisierung.
 *
 * @package Kipphard\Angebotsanfrage
 */

namespace Kipphard\Angebotsanfrage;

defined( 'ABSPATH' ) || exit;

/**
 * Zustandslose Hilfsmethoden, die im gesamten Plugin genutzt werden.
 */
class Helpers {

	/** Erforderliche Berechtigung für alle Admin-Aktionen. */
	const CAP = 'manage_options';

	/** Options-Key für die Plugin-Einstellungen. */
	const OPT_SETTINGS = 'aga_settings';

	/**
	 * Prüft ob die Pro-Lizenz aktiv ist. Standardmäßig false.
	 *
	 * @return bool
	 */
	public static function is_pro() {
		return (bool) apply_filters( 'aga_is_pro', defined( 'AGA_PRO' ) && AGA_PRO );
	}

	/**
	 * Liefert die Standard-Einstellungen des Plugins.
	 *
	 * @return array<string,mixed>
	 */
	public static function defaults() {
		return array(
			'button_label'    => 'Angebot anfragen',
			'mode'            => 'add',
			'apply_to'        => 'all',
			'categories'      => array(),
			'hide_price'      => false,
			'recipient_email' => get_bloginfo( 'admin_email' ),
			'field_name'      => true,
			'field_company'   => true,
			'field_phone'     => false,
			'field_message'   => true,
		);
	}

	/**
	 * Liest eine einzelne Einstellung (mit Fallback auf den Standardwert).
	 *
	 * @param string $key Einstellungsschlüssel.
	 * @return mixed
	 */
	public static function get( $key ) {
		$settings = (array) get_option( self::OPT_SETTINGS, array() );
		$defaults = self::defaults();
		return isset( $settings[ $key ] ) ? $settings[ $key ] : ( isset( $defaults[ $key ] ) ? $defaults[ $key ] : null );
	}

	/**
	 * Sanitisiert die Einstellungsfelder streng pro Feld.
	 *
	 * @param array<string,mixed> $raw Rohe $_POST-Daten.
	 * @return array<string,mixed>
	 */
	public static function sanitize_settings( array $raw ) {
		$allowed_modes    = array( 'add', 'replace' );
		$allowed_apply_to = array( 'all', 'categories' );

		$mode = isset( $raw['mode'] ) ? sanitize_key( $raw['mode'] ) : 'add';
		if ( ! in_array( $mode, $allowed_modes, true ) ) {
			$mode = 'add';
		}

		$apply_to = isset( $raw['apply_to'] ) ? sanitize_key( $raw['apply_to'] ) : 'all';
		if ( ! in_array( $apply_to, $allowed_apply_to, true ) ) {
			$apply_to = 'all';
		}

		$categories = array();
		if ( isset( $raw['categories'] ) && is_array( $raw['categories'] ) ) {
			foreach ( $raw['categories'] as $cat_id ) {
				$id = absint( $cat_id );
				if ( $id > 0 ) {
					$categories[] = $id;
				}
			}
		}

		$recipient_email = isset( $raw['recipient_email'] ) ? sanitize_email( wp_unslash( $raw['recipient_email'] ) ) : '';
		if ( empty( $recipient_email ) ) {
			$recipient_email = get_bloginfo( 'admin_email' );
		}

		return array(
			'button_label'    => isset( $raw['button_label'] ) ? sanitize_text_field( wp_unslash( $raw['button_label'] ) ) : 'Angebot anfragen',
			'mode'            => $mode,
			'apply_to'        => $apply_to,
			'categories'      => $categories,
			'hide_price'      => ! empty( $raw['hide_price'] ),
			'recipient_email' => $recipient_email,
			'field_name'      => ! empty( $raw['field_name'] ),
			'field_company'   => ! empty( $raw['field_company'] ),
			'field_phone'     => ! empty( $raw['field_phone'] ),
			'field_message'   => ! empty( $raw['field_message'] ),
		);
	}

	/**
	 * Prüft einen Admin-POST-Request: Berechtigung + Nonce. Bricht bei Fehler ab.
	 *
	 * @param string $action Nonce-Aktion.
	 * @param string $field  Nonce-Feldname.
	 */
	public static function guard_post( $action, $field = '_wpnonce' ) {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'Keine Berechtigung.', 'angebotsanfrage' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( $action, $field );
	}
}

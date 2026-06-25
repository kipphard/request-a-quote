<?php
/**
 * Plugin-Bootstrap: Hooks, Submodule und WooCommerce-Prüfung.
 *
 * @package Kipphard\Angebotsanfrage
 */

namespace Kipphard\Angebotsanfrage;

defined( 'ABSPATH' ) || exit;

/**
 * Singleton-Einstiegspunkt.
 */
final class Plugin {

	/** @var Plugin|null */
	private static $instance = null;

	/**
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private Konstruktor (Singleton).
	 */
	private function __construct() {}

	/**
	 * Aktivierung: Standard-Einstellungen anlegen, CPT registrieren, Rewrite flushen.
	 */
	public static function activate() {
		if ( false === get_option( Helpers::OPT_SETTINGS, false ) ) {
			add_option( Helpers::OPT_SETTINGS, Helpers::defaults() );
		}
		// CPT registrieren damit flush_rewrite_rules() greift.
		Quote_Cpt::register();
		flush_rewrite_rules();
	}

	/**
	 * Laufzeit-Hooks registrieren.
	 */
	public function boot() {
		load_plugin_textdomain(
			'angebotsanfrage',
			false,
			dirname( plugin_basename( AGA_FILE ) ) . '/languages'
		);

		// WooCommerce ist Pflicht – ohne es läuft nichts.
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', array( $this, 'notice_woocommerce_missing' ) );
			return;
		}

		( new Quote_List() )->hooks();
		( new Frontend() )->hooks();
		( new Quote_Cpt() )->hooks();

		if ( is_admin() ) {
			( new Admin() )->hooks();
		}

		// Pro-only: nur laden wenn die Datei im Build vorhanden ist.
		if ( class_exists( __NAMESPACE__ . '\\Order_Converter' ) ) {
			( new Order_Converter() )->hooks();
		}
	}

	/**
	 * Admin-Hinweis wenn WooCommerce nicht aktiv ist.
	 */
	public function notice_woocommerce_missing() {
		?>
		<div class="notice notice-error">
			<p>
				<strong><?php esc_html_e( 'Angebotsanfrage', 'angebotsanfrage' ); ?>:</strong>
				<?php esc_html_e( 'WooCommerce muss installiert und aktiviert sein, damit dieses Plugin funktioniert.', 'angebotsanfrage' ); ?>
			</p>
		</div>
		<?php
	}
}

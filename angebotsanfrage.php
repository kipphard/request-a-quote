<?php
/**
 * Plugin Name:       Angebotsanfrage – Request a Quote für WooCommerce
 * Plugin URI:        https://products.kipphard.com/angebotsanfrage
 * Description:       Ermöglicht B2B- und Großhandels-Kunden, Produkte in eine Angebotsliste zu legen und eine Angebotsanfrage abzuschicken – ohne Preise anzeigen zu müssen. Saubere UX, ehrlicher Umfang.
 * Version:           0.1.0
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Author:            André Kipphard
 * Author URI:        https://kipphard.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       angebotsanfrage
 * Domain Path:       /languages
 *
 * @package Kipphard\Angebotsanfrage
 */

defined( 'ABSPATH' ) || exit;

define( 'AGA_VERSION', '0.1.0' );
define( 'AGA_FILE', __FILE__ );
define( 'AGA_DIR', plugin_dir_path( __FILE__ ) );
define( 'AGA_URL', plugin_dir_url( __FILE__ ) );
define( 'AGA_SLUG', 'angebotsanfrage' );

/**
 * Minimaler PSR-4-Autoloader für den Kipphard\Angebotsanfrage\-Namespace.
 * Kipphard\Angebotsanfrage\Foo_Bar → includes/class-foo-bar.php
 */
spl_autoload_register(
	static function ( $class ) {
		$prefix = 'Kipphard\\Angebotsanfrage\\';
		if ( strncmp( $class, $prefix, strlen( $prefix ) ) !== 0 ) {
			return;
		}
		$relative = substr( $class, strlen( $prefix ) );
		$file     = 'class-' . strtolower( str_replace( '_', '-', $relative ) ) . '.php';
		$path     = AGA_DIR . 'includes/' . $file;
		if ( is_readable( $path ) ) {
			require_once $path;
		}
	}
);

register_activation_hook( __FILE__, array( '\Kipphard\Angebotsanfrage\Plugin', 'activate' ) );

add_action(
	'plugins_loaded',
	static function () {
		\Kipphard\Angebotsanfrage\Plugin::instance()->boot();
	}
);

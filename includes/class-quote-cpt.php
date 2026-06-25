<?php
/**
 * Custom Post Type „aga_quote" registrieren und im Admin anzeigen.
 *
 * @package Kipphard\Angebotsanfrage
 */

namespace Kipphard\Angebotsanfrage;

defined( 'ABSPATH' ) || exit;

/**
 * Registriert den CPT und verwaltet Admin-Spalten + Metabox.
 */
class Quote_Cpt {

	/**
	 * Hooks registrieren.
	 */
	public function hooks() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_filter( 'manage_aga_quote_posts_columns', array( $this, 'columns' ) );
		add_action( 'manage_aga_quote_posts_custom_column', array( $this, 'column_content' ), 10, 2 );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
	}

	/**
	 * CPT registrieren (statisch, damit activate() es aufrufen kann).
	 */
	public static function register() {
		register_post_type(
			'aga_quote',
			array(
				'labels'              => array(
					'name'               => __( 'Angebotsanfragen', 'angebotsanfrage' ),
					'singular_name'      => __( 'Angebotsanfrage', 'angebotsanfrage' ),
					'menu_name'          => __( 'Angebotsanfragen', 'angebotsanfrage' ),
					'all_items'          => __( 'Alle Anfragen', 'angebotsanfrage' ),
					'view_item'          => __( 'Anfrage ansehen', 'angebotsanfrage' ),
					'search_items'       => __( 'Anfragen suchen', 'angebotsanfrage' ),
					'not_found'          => __( 'Keine Anfragen gefunden.', 'angebotsanfrage' ),
					'not_found_in_trash' => __( 'Keine Anfragen im Papierkorb.', 'angebotsanfrage' ),
				),
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_admin_bar'   => false,
				'show_in_rest'        => false,
				'capability_type'     => 'post',
				'capabilities'        => array(
					'create_posts' => 'do_not_allow',
				),
				'map_meta_cap'        => true,
				'supports'            => array( 'title' ),
				'menu_icon'           => 'dashicons-clipboard',
				'menu_position'       => 58,
				'has_archive'         => false,
				'rewrite'             => false,
				'query_var'           => false,
			)
		);
	}

	/**
	 * Admin-Spalten definieren.
	 *
	 * @param array<string,string> $columns Vorhandene Spalten.
	 * @return array<string,string>
	 */
	public function columns( array $columns ) {
		unset( $columns['date'] );
		$columns['aga_customer']    = __( 'Kunde', 'angebotsanfrage' );
		$columns['aga_items_count'] = __( 'Produkte', 'angebotsanfrage' );
		$columns['date']            = __( 'Datum', 'angebotsanfrage' );
		return $columns;
	}

	/**
	 * Inhalt der Admin-Spalten ausgeben.
	 *
	 * @param string $column  Spaltenname.
	 * @param int    $post_id Post-ID.
	 */
	public function column_content( $column, $post_id ) {
		if ( 'aga_customer' === $column ) {
			$name    = get_post_meta( $post_id, '_aga_name', true );
			$email   = get_post_meta( $post_id, '_aga_email', true );
			$company = get_post_meta( $post_id, '_aga_company', true );
			echo esc_html( $name );
			if ( $company ) {
				echo ' <span style="color:#646970;">(' . esc_html( $company ) . ')</span>';
			}
			echo '<br><a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>';
		}

		if ( 'aga_items_count' === $column ) {
			$items = get_post_meta( $post_id, '_aga_items', true );
			$items = is_array( $items ) ? $items : array();
			echo esc_html( count( $items ) );
		}
	}

	/**
	 * Metabox registrieren.
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'aga_quote_details',
			__( 'Angebotsdetails', 'angebotsanfrage' ),
			array( $this, 'render_meta_box' ),
			'aga_quote',
			'normal',
			'high'
		);
	}

	/**
	 * Metabox rendern (nur lesend, alle Ausgaben escaped).
	 *
	 * @param \WP_Post $post Aktueller Post.
	 */
	public function render_meta_box( $post ) {
		$name    = get_post_meta( $post->ID, '_aga_name', true );
		$email   = get_post_meta( $post->ID, '_aga_email', true );
		$company = get_post_meta( $post->ID, '_aga_company', true );
		$phone   = get_post_meta( $post->ID, '_aga_phone', true );
		$message = get_post_meta( $post->ID, '_aga_message', true );
		$items   = get_post_meta( $post->ID, '_aga_items', true );
		$items   = is_array( $items ) ? $items : array();
		?>
		<div class="aga-meta-box">
			<h3><?php esc_html_e( 'Kontaktdaten', 'angebotsanfrage' ); ?></h3>
			<table class="form-table" style="max-width:600px;">
				<?php if ( $name ) : ?>
				<tr>
					<th><?php esc_html_e( 'Name', 'angebotsanfrage' ); ?></th>
					<td><?php echo esc_html( $name ); ?></td>
				</tr>
				<?php endif; ?>
				<?php if ( $email ) : ?>
				<tr>
					<th><?php esc_html_e( 'E-Mail', 'angebotsanfrage' ); ?></th>
					<td><a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a></td>
				</tr>
				<?php endif; ?>
				<?php if ( $company ) : ?>
				<tr>
					<th><?php esc_html_e( 'Unternehmen', 'angebotsanfrage' ); ?></th>
					<td><?php echo esc_html( $company ); ?></td>
				</tr>
				<?php endif; ?>
				<?php if ( $phone ) : ?>
				<tr>
					<th><?php esc_html_e( 'Telefon', 'angebotsanfrage' ); ?></th>
					<td><?php echo esc_html( $phone ); ?></td>
				</tr>
				<?php endif; ?>
				<?php if ( $message ) : ?>
				<tr>
					<th><?php esc_html_e( 'Nachricht', 'angebotsanfrage' ); ?></th>
					<td><?php echo nl2br( esc_html( $message ) ); ?></td>
				</tr>
				<?php endif; ?>
			</table>

			<h3><?php esc_html_e( 'Angefragte Produkte', 'angebotsanfrage' ); ?></h3>
			<?php if ( empty( $items ) ) : ?>
				<p><?php esc_html_e( 'Keine Produkte gespeichert.', 'angebotsanfrage' ); ?></p>
			<?php else : ?>
				<table class="widefat fixed striped" style="max-width:600px;">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Produkt', 'angebotsanfrage' ); ?></th>
							<th style="width:80px;"><?php esc_html_e( 'Menge', 'angebotsanfrage' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $items as $item ) : ?>
							<?php
							$product_id = isset( $item['product_id'] ) ? absint( $item['product_id'] ) : 0;
							$qty        = isset( $item['qty'] ) ? absint( $item['qty'] ) : 1;
							$product    = $product_id ? wc_get_product( $product_id ) : null;
							?>
							<tr>
								<td>
									<?php if ( $product ) : ?>
										<a href="<?php echo esc_url( get_edit_post_link( $product_id ) ); ?>">
											<?php echo esc_html( $product->get_name() ); ?>
										</a>
										<span style="color:#646970;font-size:11px;"> (#<?php echo esc_html( $product_id ); ?>)</span>
									<?php else : ?>
										<?php echo esc_html( sprintf( __( 'Produkt #%d (gelöscht)', 'angebotsanfrage' ), $product_id ) ); ?>
									<?php endif; ?>
								</td>
								<td><?php echo esc_html( $qty ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}
}

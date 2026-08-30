<?php
namespace DriveVault\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * WooCommerce Product Editor Metabox & Downloadable Files Integration.
 */
class ProductMetabox {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_footer', array( $this, 'render_modal_portal_root' ) );
	}

	/**
	 * Render modal root element in admin footer for product edit screen.
	 */
	public function render_modal_portal_root() {
		$screen = get_current_screen();
		if ( ! $screen || 'product' !== $screen->post_type ) {
			return;
		}
		?>
		<div id="drivevault-product-picker-modal-root"></div>
		<?php
	}
}

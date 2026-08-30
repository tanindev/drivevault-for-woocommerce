<?php
namespace DriveVault\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Admin Menu Page Registration.
 */
class AdminMenu {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ), 50 );
		add_filter( 'plugin_action_links_' . plugin_basename( DRIVEVAULT_FILE ), array( $this, 'add_action_links' ) );
	}

	/**
	 * Add Settings action link on the plugins list page.
	 *
	 * @param array $links Current plugin action links.
	 * @return array
	 */
	public function add_action_links( $links ) {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=drivevault-settings' ) ),
			esc_html__( 'Settings', 'drivevault-for-woocommerce' )
		);
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Register WooCommerce submenu page for Google Drive.
	 */
	public function register_menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Google Drive Downloads', 'drivevault-for-woocommerce' ),
			__( 'Google Drive', 'drivevault-for-woocommerce' ),
			'manage_woocommerce',
			'drivevault-settings',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Render React root container.
	 */
	public function render_page() {
		?>
		<div class="wrap drivevault-admin-wrap">
			<div id="drivevault-admin-root">
				<div class="drivevault-loading-placeholder" style="padding: 40px; text-align: center;">
					<h2><?php esc_html_e( 'Loading Google Drive Integration...', 'drivevault-for-woocommerce' ); ?></h2>
				</div>
			</div>
		</div>
		<?php
	}
}

<?php
/**
 * Plugin Name:       DriveVault for WooCommerce
 * Plugin URI:        https://github.com/tanindev/drivevault-for-woocommerce
 * Description:       Seamlessly attach and serve WooCommerce downloadable products directly from Google Drive with a modern React UI.
 * Version:           1.0.0
 * Author:            Tanin's Dev
 * Author URI:        https://profiles.wordpress.org/tanindev/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       drivevault-for-woocommerce
 * Domain Path:       /languages
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Requires Plugins: woocommerce
 */

defined( 'ABSPATH' ) || exit;

define( 'DRIVEVAULT_VERSION', '1.0.0' );
define( 'DRIVEVAULT_FILE', __FILE__ );
define( 'DRIVEVAULT_PATH', plugin_dir_path( __FILE__ ) );
define( 'DRIVEVAULT_URL', plugin_dir_url( __FILE__ ) );
define( 'DRIVEVAULT_ASSETS_URL', DRIVEVAULT_URL . 'assets/' );
define( 'DRIVEVAULT_OPTION_SETTINGS', 'drivevault_settings' );
define( 'DRIVEVAULT_OPTION_TOKENS', 'drivevault_google_tokens' );

/**
 * Autoloader for Plugin Classes.
 */
spl_autoload_register( function ( $class ) {
	$prefix = 'DriveVault\\';
	$base_dir = DRIVEVAULT_PATH . 'includes/';

	$len = strlen( $prefix );
	if ( strncmp( $prefix, $class, $len ) !== 0 ) {
		return;
	}

	$relative_class = substr( $class, $len );
	$file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

	if ( file_exists( $file ) ) {
		require_once $file;
	}
} );

/**
 * Declare WooCommerce HPOS (High-Performance Order Storage) Compatibility.
 */
add_action( 'before_woocommerce_init', function () {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', DRIVEVAULT_FILE, true );
	}
} );

/**
 * Check dependencies and initialize plugin.
 */
function drivevault_init_plugin() {
	// Check if WooCommerce is installed and active.
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', function () {
			?>
			<div class="notice notice-warning is-dismissible">
				<p>
					<strong><?php esc_html_e( 'DriveVault for WooCommerce', 'drivevault-for-woocommerce' ); ?></strong>
					<?php esc_html_e( 'requires WooCommerce to be installed and active.', 'drivevault-for-woocommerce' ); ?>
				</p>
			</div>
			<?php
		} );
		return;
	}

	// Initialize main plugin singleton.
	\DriveVault\Plugin::instance();
}
add_action( 'plugins_loaded', 'drivevault_init_plugin' );

/**
 * Activation Hook.
 */
register_activation_hook( __FILE__, function () {
	// Setup default options if not exists.
	$default_settings = array(
		'client_id'          => '',
		'client_secret'      => '',
		'download_method'    => 'stream', // 'stream' or 'redirect'
		'cache_ttl'          => 600,      // 10 minutes
		'chunk_size_mb'      => 8,        // 8MB stream chunks
		'enable_shared_drive'=> true,
	);

	if ( false === get_option( DRIVEVAULT_OPTION_SETTINGS ) ) {
		update_option( DRIVEVAULT_OPTION_SETTINGS, $default_settings );
	}
} );

/**
 * Deactivation Hook.
 */
register_deactivation_hook( __FILE__, function () {
	// Clean up transients.
	\DriveVault\Helpers\Cache::flush_all();
} );

/**
 * Load a template file with args and allow theme overrides.
 *
 * @param string $template_name Template name (e.g. 'download-error.php').
 * @param array  $args          Template variables.
 * @param string $template_path Custom template path within theme.
 * @param string $default_path  Fallback directory in plugin.
 */
function drivevault_get_template( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
	\DriveVault\Helpers\Template::get_template( $template_name, $args, $template_path, $default_path );
}

/**
 * Locate a template file path checking theme override first.
 *
 * @param string $template_name Template name.
 * @param string $template_path Custom template path within theme.
 * @param string $default_path  Fallback directory in plugin.
 * @return string
 */
function drivevault_locate_template( $template_name, $template_path = '', $default_path = '' ) {
	return \DriveVault\Helpers\Template::locate_template( $template_name, $template_path, $default_path );
}

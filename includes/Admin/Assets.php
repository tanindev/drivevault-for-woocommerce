<?php
namespace DriveVault\Admin;

use DriveVault\Services\OAuthManager;

defined( 'ABSPATH' ) || exit;

/**
 * Admin Assets Handler.
 */
class Assets {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue scripts and styles targeted by screen.
	 *
	 * @param string $hook Admin page hook.
	 */
	public function enqueue_assets( $hook ) {
		$screen           = get_current_screen();
		$is_settings_page = ( 'woocommerce_page_drivevault-settings' === $hook || ( $screen && 'woocommerce_page_drivevault-settings' === $screen->id ) );
		$is_product_page  = ( $screen && 'product' === $screen->post_type );

		if ( ! $is_settings_page && ! $is_product_page ) {
			return;
		}

		$oauth = new OAuthManager();

		// Common localized data for React apps.
		$localized_data = array(
			'restUrl'     => esc_url_raw( rest_url( 'drivevault/v1/' ) ),
			'nonce'       => wp_create_nonce( 'wp_rest' ),
			'isConnected' => $oauth->is_connected(),
			'pluginUrl'   => DRIVEVAULT_URL,
			'assetsUrl'   => DRIVEVAULT_ASSETS_URL,
			'i18n'        => array(
				'selectFromDrive'  => __( 'Add Google Drive File', 'drivevault-for-woocommerce' ),
				'googleDrive'      => __( 'Google Drive', 'drivevault-for-woocommerce' ),
				'searchPlaceholder'=> __( 'Search files in Google Drive...', 'drivevault-for-woocommerce' ),
				'insertFiles'      => __( 'Insert Selected Files', 'drivevault-for-woocommerce' ),
				'cancel'           => __( 'Cancel', 'drivevault-for-woocommerce' ),
				'noFiles'          => __( 'No files found in this folder.', 'drivevault-for-woocommerce' ),
				'connecting'       => __( 'Connecting...', 'drivevault-for-woocommerce' ),
				'connected'        => __( 'Connected', 'drivevault-for-woocommerce' ),
				'disconnected'     => __( 'Disconnected', 'drivevault-for-woocommerce' ),
			),
		);

		// Always enqueue wp-components CSS for Gutenberg/WPDS components.
		wp_enqueue_style( 'wp-components' );

		// 1. Settings Page App
		if ( $is_settings_page ) {
			$settings_controller       = new \DriveVault\API\Controllers\SettingsController( $oauth );
			$current_settings          = get_option( DRIVEVAULT_OPTION_SETTINGS, array() );
			$localized_data['settings'] = $settings_controller->format_settings_response( $current_settings );

			$asset_file = DRIVEVAULT_PATH . 'assets/build/index.asset.php';
			$asset_info = file_exists( $asset_file ) ? require $asset_file : array(
				'dependencies' => array( 'wp-element', 'wp-components', 'wp-api-fetch', 'wp-data', 'wp-i18n' ),
				'version'      => DRIVEVAULT_VERSION,
			);

			wp_enqueue_script(
				'drivevault-admin-settings',
				DRIVEVAULT_ASSETS_URL . 'build/index.js',
				$asset_info['dependencies'],
				$asset_info['version'],
				true
			);

			wp_localize_script( 'drivevault-admin-settings', 'drivevaultData', $localized_data );
			wp_set_script_translations( 'drivevault-admin-settings', 'drivevault-for-woocommerce', DRIVEVAULT_PATH . 'languages' );

			if ( file_exists( DRIVEVAULT_PATH . 'assets/build/index.css' ) ) {
				wp_enqueue_style(
					'drivevault-admin-settings',
					DRIVEVAULT_ASSETS_URL . 'build/index.css',
					array( 'wp-components' ),
					$asset_info['version']
				);
			}
		}

		// 2. Product Edit Page Picker
		if ( $is_product_page ) {
			$picker_asset_file = DRIVEVAULT_PATH . 'assets/build/product-picker.asset.php';
			$picker_asset_info = file_exists( $picker_asset_file ) ? require $picker_asset_file : array(
				'dependencies' => array( 'wp-element', 'wp-components', 'wp-api-fetch', 'wp-data', 'wp-i18n', 'jquery' ),
				'version'      => DRIVEVAULT_VERSION,
			);

			wp_enqueue_script(
				'drivevault-product-picker',
				DRIVEVAULT_ASSETS_URL . 'build/product-picker.js',
				$picker_asset_info['dependencies'],
				$picker_asset_info['version'],
				true
			);

			wp_localize_script( 'drivevault-product-picker', 'drivevaultData', $localized_data );
			wp_set_script_translations( 'drivevault-product-picker', 'drivevault-for-woocommerce', DRIVEVAULT_PATH . 'languages' );

			if ( file_exists( DRIVEVAULT_PATH . 'assets/build/product-picker.css' ) ) {
				wp_enqueue_style(
					'drivevault-product-picker',
					DRIVEVAULT_ASSETS_URL . 'build/product-picker.css',
					array( 'wp-components' ),
					$picker_asset_info['version']
				);
			}
		}
	}
}

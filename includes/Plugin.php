<?php
namespace DriveVault;

defined( 'ABSPATH' ) || exit;

/**
 * Main Singleton Plugin Class.
 */
class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Services.
	 */
	public $oauth;
	public $drive;
	public $download_handler;
	public $admin_menu;
	public $assets;
	public $product_metabox;
	public $rest_server;

	/**
	 * Get singleton instance.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->init_services();
		$this->init_hooks();
	}

	/**
	 * Instantiate all core services.
	 */
	private function init_services() {
		$this->oauth            = new Services\OAuthManager();
		$this->drive            = new Services\GoogleDriveClient( $this->oauth );
		$this->download_handler = new Services\DownloadHandler( $this->drive );
		$this->admin_menu       = new Admin\AdminMenu();
		$this->assets           = new Admin\Assets();
		$this->product_metabox  = new Admin\ProductMetabox();
		$this->rest_server      = new API\RestServer( $this->oauth, $this->drive );
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		// Register Google Drive scheme with WooCommerce Approved Download Directories.
		add_action( 'admin_init', array( $this, 'ensure_approved_directories' ), 5 );
		add_action( 'woocommerce_init', array( $this, 'ensure_approved_directories' ), 5 );
		add_action( 'save_post_product', array( $this, 'ensure_approved_directories' ), 1 );
	}

	/**
	 * Ensure Google Drive download scheme is registered in WooCommerce Approved Download Directories.
	 */
	public function ensure_approved_directories() {
		if ( get_transient( 'drivevault_approved_dirs_synced' ) ) {
			return;
		}

		global $wpdb;

		$table_name = esc_sql( $wpdb->prefix . 'wc_product_download_directories' );

		// Verify table exists.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_name ) ) ) !== $table_name ) {
			return;
		}

		$approved_urls = array(
			'gdrive://file/',
			'gdrive://',
			'gdrive://drive.google.com/',
		);

		foreach ( $approved_urls as $url ) {
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$exists = $wpdb->get_var(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
				$wpdb->prepare( "SELECT url_id FROM {$table_name} WHERE url = %s", $url ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			);
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			if ( ! $exists ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->insert(
					$table_name,
					array(
						'url'     => $url,
						'enabled' => 1,
					)
				);
			} else {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->update(
					$table_name,
					array( 'enabled' => 1 ),
					array( 'url' => $url )
				);
			}
		}

		set_transient( 'drivevault_approved_dirs_synced', 1, DAY_IN_SECONDS );
	}
}

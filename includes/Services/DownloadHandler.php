<?php
namespace DriveVault\Services;

defined( 'ABSPATH' ) || exit;

/**
 * WooCommerce Download Interceptor and Secure File Streamer.
 */
class DownloadHandler {

	/**
	 * Google Drive Client.
	 *
	 * @var GoogleDriveClient
	 */
	protected $drive;

	/**
	 * Constructor.
	 *
	 * @param GoogleDriveClient $drive
	 */
	public function __construct( GoogleDriveClient $drive ) {
		$this->drive = $drive;
		$this->init_hooks();
	}

	/**
	 * Register WooCommerce download hooks.
	 */
	private function init_hooks() {
		// Hook into WooCommerce download execution before standard local file handler.
		add_action( 'woocommerce_download_file_force', array( $this, 'handle_download' ), 1, 2 );
		add_action( 'woocommerce_download_file_redirect', array( $this, 'handle_download' ), 1, 2 );
		add_action( 'woocommerce_download_file_xsendfile', array( $this, 'handle_download' ), 1, 2 );

		// Tell WooCommerce that Google Drive files exist (bypasses local server filesystem check).
		add_filter( 'woocommerce_downloadable_file_exists', array( $this, 'validate_file_exists' ), 10, 2 );
		add_filter( 'woocommerce_is_download_path_valid', array( $this, 'validate_file_exists' ), 10, 2 );
	}

	/**
	 * Bypass WooCommerce local file_exists check for Google Drive custom schemes.
	 *
	 * @param bool   $exists
	 * @param string $file_url
	 * @return bool
	 */
	public function validate_file_exists( $exists, $file_url ) {
		if ( strpos( $file_url, 'gdrive://' ) === 0 || strpos( $file_url, 'googledrive://' ) === 0 ) {
			return true;
		}
		return $exists;
	}

	/**
	 * Handle WooCommerce downloadable product file request.
	 *
	 * @param string $file_path File path / URL from WooCommerce.
	 * @param string $filename  Custom filename if defined in product.
	 */
	public function handle_download( $file_path, $filename = '' ) {
		$file_id = GoogleDriveClient::parse_file_id( $file_path );
		if ( ! $file_id ) {
			return; // Not a Google Drive file, let WooCommerce handle standard local files.
		}

		$settings = get_option( DRIVEVAULT_OPTION_SETTINGS, array() );
		$download_method = ! empty( $settings['download_method'] ) ? $settings['download_method'] : 'redirect';

		// Fetch file metadata from Google Drive.
		$file_meta = $this->drive->get_file( $file_id );
		if ( is_wp_error( $file_meta ) ) {
			$error_message = ! empty( $settings['error_message_disconnected'] )
				? $settings['error_message_disconnected']
				: sprintf(
					/* translators: %s: Error message from Google Drive API */
					__( 'Error accessing Google Drive file: %s', 'drivevault-for-woocommerce' ),
					$file_meta->get_error_message()
				);

			$this->render_error_page(
				$error_message,
				__( 'Download Unavailable', 'drivevault-for-woocommerce' ),
				502
			);
		}

		// Ensure true filename and extension from Google Drive metadata is preserved
		if ( ! empty( $file_meta['name'] ) ) {
			$real_filename = $file_meta['name'];
		} elseif ( ! empty( $filename ) && $filename !== $file_id && $filename !== basename( $file_path ) ) {
			$real_filename = $filename;
		} else {
			$real_filename = 'download';
		}

		$raw_mime_type = ! empty( $file_meta['mimeType'] ) ? $file_meta['mimeType'] : 'application/octet-stream';
		$mime_type     = sanitize_mime_type( $raw_mime_type );
		if ( empty( $mime_type ) ) {
			$mime_type = 'application/octet-stream';
		}
		$file_size = ! empty( $file_meta['size'] ) ? (float) $file_meta['size'] : 0;

		// Allow Pro add-on or custom handlers to intercept download (e.g. for server-side chunked proxy streaming).
		$handled = apply_filters( 'drivevault_pre_handle_download', false, $file_id, $file_meta, $real_filename, $download_method );
		if ( $handled ) {
			exit;
		}

		// Direct Google Drive native download delivery via safe redirect.
		$download_url = ! empty( $file_meta['webContentLink'] )
			? $file_meta['webContentLink']
			: sprintf( 'https://drive.google.com/uc?id=%s&export=download', rawurlencode( $file_id ) );

		$redirect_url  = esc_url_raw( $download_url );
		$redirect_host = wp_parse_url( $redirect_url, PHP_URL_HOST );
		if ( $redirect_host ) {
			add_filter(
				'allowed_redirect_hosts',
				function ( $hosts ) use ( $redirect_host ) {
					$hosts[] = $redirect_host;
					return $hosts;
				}
			);
		}

		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Render error page using the DriveVault template system.
	 *
	 * @param string $message     Error message to display.
	 * @param string $title       Page title.
	 * @param int    $status_code HTTP response status code.
	 */
	protected function render_error_page( $message, $title = '', $status_code = 502 ) {
		if ( empty( $title ) ) {
			$title = __( 'Download Unavailable', 'drivevault-for-woocommerce' );
		}

		$account_url = function_exists( 'wc_get_account_endpoint_url' ) ? wc_get_account_endpoint_url( 'downloads' ) : '';
		if ( empty( $account_url ) && function_exists( 'wc_get_page_permalink' ) ) {
			$account_url = wc_get_page_permalink( 'myaccount' );
		}
		if ( empty( $account_url ) ) {
			$account_url = home_url( '/' );
		}

		$template_args = array(
			'message'     => $message,
			'title'       => $title,
			'status_code' => $status_code,
			'site_name'   => get_bloginfo( 'name' ),
			'home_url'    => home_url( '/' ),
			'account_url' => $account_url,
		);

		drivevault_get_template( 'download-error.php', $template_args );
		exit;
	}
}

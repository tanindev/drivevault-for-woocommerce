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
		$download_method = ! empty( $settings['download_method'] ) ? $settings['download_method'] : 'stream';

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

		$mime_type = ! empty( $file_meta['mimeType'] ) ? $file_meta['mimeType'] : 'application/octet-stream';
		$file_size = ! empty( $file_meta['size'] ) ? (float) $file_meta['size'] : 0;

		if ( 'redirect' === $download_method && ! empty( $file_meta['webContentLink'] ) ) {
			$redirect_url  = esc_url_raw( $file_meta['webContentLink'] );
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

		// Direct Stream delivery.
		$this->stream_file( $file_id, $real_filename, $mime_type, $file_size );
	}

	/**
	 * Stream file from Google Drive API directly to customer's browser.
	 *
	 * @param string $file_id
	 * @param string $filename
	 * @param string $mime_type
	 * @param float  $file_size
	 */
	protected function stream_file( $file_id, $filename, $mime_type, $file_size ) {
		// Get fresh access token.
		$oauth        = new OAuthManager();
		$access_token = $oauth->get_access_token();

		if ( is_wp_error( $access_token ) ) {
			$settings      = get_option( DRIVEVAULT_OPTION_SETTINGS, array() );
			$error_message = ! empty( $settings['error_message_disconnected'] )
				? $settings['error_message_disconnected']
				: __( 'Google Drive authentication expired. Please contact site administrator.', 'drivevault-for-woocommerce' );

			$this->render_error_page(
				$error_message,
				__( 'Download Unavailable', 'drivevault-for-woocommerce' ),
				500
			);
		}

		$download_url = sprintf(
			'https://www.googleapis.com/drive/v3/files/%s?alt=media&supportsAllDrives=true',
			urlencode( $file_id )
		);

		// Clean output buffers.
		while ( ob_get_level() ) {
			ob_end_clean();
		}

		// Disable compression & buffering.
		if ( function_exists( 'apache_setenv' ) ) {
			@apache_setenv( 'no-gzip', '1' );
		}
		// phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged -- Output compression disabled for real-time binary streaming.
		@ini_set( 'zlib.output_compression', 'Off' );
		// phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged -- Execution time limit removed for large file downloads.
		@set_time_limit( 0 );

		$headers = array(
			'Authorization: Bearer ' . $access_token,
		);

		// Handle HTTP Range header if requested.
		$http_range = isset( $_SERVER['HTTP_RANGE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_RANGE'] ) ) : '';
		if ( ! empty( $http_range ) ) {
			$headers[] = 'Range: ' . $http_range;
		}

		// phpcs:disable WordPress.WP.AlternativeFunctions.curl_curl_init, WordPress.WP.AlternativeFunctions.curl_curl_setopt, WordPress.WP.AlternativeFunctions.curl_curl_exec, WordPress.WP.AlternativeFunctions.curl_curl_error, WordPress.WP.AlternativeFunctions.curl_curl_getinfo, WordPress.WP.AlternativeFunctions.curl_curl_close -- Direct cURL streaming required for chunked piping of large Google Drive files to avoid memory exhaustion.
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $download_url );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, false );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, true );
		curl_setopt( $ch, CURLOPT_BUFFERSIZE, 1024 * 64 ); // 64KB buffer

		// Header callback to forward relevant headers to customer.
		$headers_sent = false;
		curl_setopt( $ch, CURLOPT_HEADERFUNCTION, function ( $ch, $header_line ) use ( &$headers_sent, $filename, $mime_type, $file_size ) {
			$len = strlen( $header_line );
			$parts = explode( ':', $header_line, 2 );

			if ( count( $parts ) === 2 ) {
				$name  = strtolower( trim( $parts[0] ) );
				$value = trim( $parts[1] );

				if ( 'content-range' === $name ) {
					header( "Content-Range: $value" );
					http_response_code( 206 );
				} elseif ( 'content-length' === $name ) {
					header( "Content-Length: $value" );
				}
			}

			// On HTTP status line
			if ( strpos( $header_line, 'HTTP/' ) === 0 ) {
				if ( strpos( $header_line, ' 206 ' ) !== false ) {
					http_response_code( 206 );
				} elseif ( strpos( $header_line, ' 200 ' ) !== false ) {
					http_response_code( 200 );
				}
			}

			if ( ! $headers_sent && ( trim( $header_line ) === '' ) ) {
				$safe_filename     = str_replace( array( '"', "\r", "\n", '/', '\\' ), '', $filename );
				$encoded_filename  = rawurlencode( $filename );

				header( 'Content-Type: ' . $mime_type );
				header( 'Content-Disposition: attachment; filename="' . $safe_filename . '"; filename*=UTF-8\'\'' . $encoded_filename );
				header( 'Accept-Ranges: bytes' );
				header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
				header( 'Pragma: public' );
				header( 'Expires: 0' );
				$headers_sent = true;
			}

			return $len;
		} );

		// Write callback to stream chunks.
		curl_setopt( $ch, CURLOPT_WRITEFUNCTION, function ( $ch, $data ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Direct binary chunk output.
			echo $data;
			if ( ob_get_length() ) {
				ob_flush();
			}
			flush();
			return strlen( $data );
		} );

		curl_exec( $ch );
		$curl_error = curl_error( $ch );
		$http_code  = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		curl_close( $ch );
		// phpcs:enable WordPress.WP.AlternativeFunctions.curl_curl_init, WordPress.WP.AlternativeFunctions.curl_curl_setopt, WordPress.WP.AlternativeFunctions.curl_curl_exec, WordPress.WP.AlternativeFunctions.curl_curl_error, WordPress.WP.AlternativeFunctions.curl_curl_getinfo, WordPress.WP.AlternativeFunctions.curl_curl_close

		if ( $http_code >= 400 || ! empty( $curl_error ) ) {
			// If stream failed before sending data
			if ( ! headers_sent() ) {
				$this->render_error_page(
					__( 'Failed to stream file from Google Drive. Please try again in a few moments.', 'drivevault-for-woocommerce' ),
					__( 'Download Failed', 'drivevault-for-woocommerce' ),
					502
				);
			}
		}

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

<?php
namespace DriveVault\Services;

defined( 'ABSPATH' ) || exit;

/**
 * Google Drive API v3 Client Service.
 */
class GoogleDriveClient {

	const API_BASE = 'https://www.googleapis.com/drive/v3';

	/**
	 * OAuth Manager instance.
	 *
	 * @var OAuthManager
	 */
	protected $oauth;

	/**
	 * Constructor.
	 *
	 * @param OAuthManager $oauth
	 */
	public function __construct( OAuthManager $oauth ) {
		$this->oauth = $oauth;
	}

	/**
	 * Perform an authorized GET request to Google Drive API.
	 *
	 * @param string $endpoint
	 * @param array  $params
	 * @param int    $timeout
	 * @return array|\WP_Error
	 */
	public function request( $endpoint, $params = array(), $timeout = 25 ) {
		$token = $this->oauth->get_access_token();
		if ( is_wp_error( $token ) ) {
			return $token;
		}

		$url = self::API_BASE . $endpoint;
		if ( ! empty( $params ) ) {
			$url = add_query_arg( $params, $url );
		}

		$response = wp_remote_get( $url, array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $token,
				'Accept'        => 'application/json',
			),
			'timeout' => $timeout,
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code >= 400 ) {
			if ( ! empty( $body['error']['message'] ) ) {
				$error_message = $body['error']['message'];
			} else {
				/* translators: %d: HTTP response status code */
				$error_message = sprintf( __( 'Google Drive API error: %d', 'drivevault-for-woocommerce' ), $code );
			}
			return new \WP_Error( 'drive_api_error', $error_message, array( 'status' => $code ) );
		}

		return $body;
	}

	/**
	 * List files and folders in Google Drive.
	 *
	 * @param array $args
	 * @param bool  $force_refresh
	 * @return array|\WP_Error
	 */
	public function list_files( $args = array(), $force_refresh = false ) {
		$folder_id    = ! empty( $args['folder_id'] ) ? sanitize_text_field( $args['folder_id'] ) : 'root';
		$search       = ! empty( $args['search'] ) ? sanitize_text_field( $args['search'] ) : '';
		$page_token   = ! empty( $args['page_token'] ) ? sanitize_text_field( $args['page_token'] ) : '';
		$page_size    = ! empty( $args['page_size'] ) ? min( 100, max( 10, absint( $args['page_size'] ) ) ) : 40;
		$filter_type  = ! empty( $args['filter_type'] ) ? sanitize_text_field( $args['filter_type'] ) : 'all'; // all, files, folders

		$cache_key = 'files_' . md5( serialize( compact( 'folder_id', 'search', 'page_token', 'page_size', 'filter_type' ) ) );

		if ( ! $force_refresh ) {
			$cached = \DriveVault\Helpers\Cache::get( $cache_key );
			if ( false !== $cached ) {
				return $cached;
			}
		}

		$query_parts = array( 'trashed = false' );

		if ( ! empty( $search ) ) {
			// Search query across drive.
			$escaped_search = addcslashes( $search, "'\\" );
			$query_parts[]  = "name contains '{$escaped_search}'";
		} else {
			// Specific folder contents.
			$escaped_folder = addcslashes( $folder_id, "'\\" );
			$query_parts[]  = "'{$escaped_folder}' in parents";
		}

		if ( 'folders' === $filter_type ) {
			$query_parts[] = "mimeType = 'application/vnd.google-apps.folder'";
		} elseif ( 'files' === $filter_type ) {
			$query_parts[] = "mimeType != 'application/vnd.google-apps.folder'";
		}

		$settings                 = get_option( DRIVEVAULT_OPTION_SETTINGS, array() );
		$has_shared_drive_support = (bool) apply_filters( 'drivevault_has_shared_drive_support', false );
		$enable_shared_drive      = $has_shared_drive_support && ! empty( $settings['enable_shared_drive'] );

		$params = array(
			'q'        => implode( ' and ', $query_parts ),
			'pageSize' => $page_size,
			'fields'   => 'nextPageToken, files(id, name, mimeType, size, modifiedTime, iconLink, thumbnailLink, webViewLink, webContentLink, md5Checksum, shared, parents, capabilities)',
			'orderBy'  => 'folder, name_natural',
		);

		if ( $enable_shared_drive ) {
			$params['supportsAllDrives']         = 'true';
			$params['includeItemsFromAllDrives'] = 'true';
		}

		if ( ! empty( $page_token ) ) {
			$params['pageToken'] = $page_token;
		}

		$response = $this->request( '/files', $params );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$items = array();
		if ( ! empty( $response['files'] ) && is_array( $response['files'] ) ) {
			foreach ( $response['files'] as $file ) {
				$mime_type = ! empty( $file['mimeType'] ) ? $file['mimeType'] : '';
				$is_folder  = ( 'application/vnd.google-apps.folder' === $mime_type );
				$type_info  = \DriveVault\Helpers\Formatter::map_mime_type( $mime_type );
				$raw_size   = ! empty( $file['size'] ) ? (float) $file['size'] : 0;

				$items[] = array(
					'id'            => $file['id'],
					'name'          => $file['name'],
					'mime_type'     => $mime_type,
					'is_folder'     => $is_folder,
					'type'          => $type_info['type'],
					'icon'          => $type_info['icon'],
					'type_label'    => $type_info['label'],
					'size'          => $raw_size,
					'formatted_size'=> $is_folder ? '' : \DriveVault\Helpers\Formatter::format_bytes( $raw_size ),
					'modified_time' => ! empty( $file['modifiedTime'] ) ? $file['modifiedTime'] : '',
					'thumbnail_link'=> ! empty( $file['thumbnailLink'] ) ? $file['thumbnailLink'] : '',
					'web_view_link' => ! empty( $file['webViewLink'] ) ? $file['webViewLink'] : '',
					'shared'        => ! empty( $file['shared'] ),
					'download_url'  => $this->build_custom_scheme( $file['id'], $file['name'], $raw_size ),
				);
			}
		}

		$result = array(
			'files'           => $items,
			'next_page_token' => ! empty( $response['nextPageToken'] ) ? $response['nextPageToken'] : null,
			'folder_id'       => $folder_id,
		);

		\DriveVault\Helpers\Cache::set( $cache_key, $result );

		return $result;
	}

	/**
	 * Build custom URL scheme for WooCommerce downloadable files table.
	 *
	 * @param string $file_id
	 * @param string $filename
	 * @param float  $size
	 * @return string
	 */
	public function build_custom_scheme( $file_id, $filename = '', $size = 0 ) {
		return sprintf( 'gdrive://file/%s', $file_id );
	}

	/**
	 * Parse a Google Drive identifier from a URL / custom scheme.
	 *
	 * @param string $url
	 * @return string|false File ID or false.
	 */
	public static function parse_file_id( $url ) {
		$url = trim( $url );
		if ( strpos( $url, 'gdrive://' ) === 0 ) {
			$path = substr( $url, 9 );
			$path = ltrim( $path, '/' );
			if ( strpos( $path, 'file/' ) === 0 ) {
				$path = substr( $path, 5 );
			}
			return strtok( $path, '/?#' );
		}
		if ( strpos( $url, 'googledrive://' ) === 0 ) {
			$path = substr( $url, 14 );
			$path = ltrim( $path, '/' );
			if ( strpos( $path, 'file/' ) === 0 ) {
				$path = substr( $path, 5 );
			}
			return strtok( $path, '/?#' );
		}
		// Match standard Google Drive shareable URLs: https://drive.google.com/file/d/{id}/view etc.
		if ( preg_match( '/drive\.google\.com\/(?:file\/d\/|open\?id=|uc\?id=)([a-zA-Z0-9_-]+)/', $url, $matches ) ) {
			return $matches[1];
		}
		return false;
	}

	/**
	 * Get metadata for a specific file.
	 *
	 * @param string $file_id
	 * @return array|\WP_Error
	 */
	public function get_file( $file_id ) {
		$cache_key = 'file_meta_' . $file_id;
		$cached    = \DriveVault\Helpers\Cache::get( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$settings                 = get_option( DRIVEVAULT_OPTION_SETTINGS, array() );
		$has_shared_drive_support = (bool) apply_filters( 'drivevault_has_shared_drive_support', false );
		$enable_shared_drive      = $has_shared_drive_support && ! empty( $settings['enable_shared_drive'] );

		$params = array(
			'fields' => 'id, name, mimeType, size, modifiedTime, webContentLink, md5Checksum, capabilities',
		);

		if ( $enable_shared_drive ) {
			$params['supportsAllDrives'] = 'true';
		}

		$file = $this->request( '/files/' . urlencode( $file_id ), $params );
		if ( is_wp_error( $file ) ) {
			return $file;
		}

		\DriveVault\Helpers\Cache::set( $cache_key, $file, 3600 );

		return $file;
	}

	/**
	 * Get Google Drive storage quota and user info.
	 *
	 * @return array|\WP_Error
	 */
	public function get_about() {
		$cache_key = 'about_quota';
		$cached    = \DriveVault\Helpers\Cache::get( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$params = array(
			'fields' => 'user, storageQuota',
		);

		$data = $this->request( '/about', $params );
		if ( is_wp_error( $data ) ) {
			return $data;
		}

		$quota = isset( $data['storageQuota'] ) ? $data['storageQuota'] : array();
		$limit = isset( $quota['limit'] ) ? (float) $quota['limit'] : 0;
		$usage = isset( $quota['usage'] ) ? (float) $quota['usage'] : 0;
		$percent = $limit > 0 ? round( ( $usage / $limit ) * 100, 1 ) : 0;

		$result = array(
			'user'             => isset( $data['user'] ) ? $data['user'] : array(),
			'limit_bytes'      => $limit,
			'usage_bytes'      => $usage,
			'limit_formatted'  => $limit > 0 ? \DriveVault\Helpers\Formatter::format_bytes( $limit ) : __( 'Unlimited', 'drivevault-for-woocommerce' ),
			'usage_formatted'  => \DriveVault\Helpers\Formatter::format_bytes( $usage ),
			'percent_used'     => $percent,
		);

		\DriveVault\Helpers\Cache::set( $cache_key, $result, 900 );

		return $result;
	}
}

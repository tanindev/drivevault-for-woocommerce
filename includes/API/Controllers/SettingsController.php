<?php
namespace DriveVault\API\Controllers;

use DriveVault\Services\OAuthManager;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * REST API Settings Controller.
 */
class SettingsController {

	const NAMESPACE = 'drivevault/v1';

	/**
	 * OAuth Manager.
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
	 * Register routes.
	 */
	public function register_routes() {
		register_rest_route( self::NAMESPACE, '/settings', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_settings' ),
				'permission_callback' => array( $this, 'check_admin_permissions' ),
			),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'update_settings' ),
				'permission_callback' => array( $this, 'check_admin_permissions' ),
			),
		) );
	}

	/**
	 * Admin permissions check.
	 */
	public function check_admin_permissions() {
		return current_user_can( 'manage_woocommerce' );
	}

	/**
	 * Get plugin settings.
	 */
	public function get_settings( WP_REST_Request $request ) {
		$settings = get_option( DRIVEVAULT_OPTION_SETTINGS, array() );

		$defaults = array(
			'client_id'                  => '',
			'client_secret'              => '',
			'download_method'            => 'stream',
			'cache_ttl'                  => 600,
			'chunk_size_mb'              => 8,
			'enable_shared_drive'        => true,
			'track_downloads'            => true,
			'delete_data_on_uninstall'   => false,
			'error_message_disconnected' => __( 'Error accessing Google Drive file: Google Drive account is not connected.', 'drivevault-for-woocommerce' ),
		);

		$merged = wp_parse_args( $settings, $defaults );

		// Mask client_secret partially if set for security
		$response_data = $merged;
		$response_data['redirect_uri'] = $this->oauth->get_redirect_uri();

		return new WP_REST_Response( $response_data, 200 );
	}

	/**
	 * Update plugin settings.
	 */
	public function update_settings( WP_REST_Request $request ) {
		$current = get_option( DRIVEVAULT_OPTION_SETTINGS, array() );

		$params = $request->get_json_params();
		if ( empty( $params ) ) {
			$params = $request->get_params();
		}

		$updated = array(
			'client_id'                  => isset( $params['client_id'] ) ? sanitize_text_field( trim( $params['client_id'] ) ) : ( isset( $current['client_id'] ) ? $current['client_id'] : '' ),
			'client_secret'              => isset( $params['client_secret'] ) ? sanitize_text_field( trim( $params['client_secret'] ) ) : ( isset( $current['client_secret'] ) ? $current['client_secret'] : '' ),
			'download_method'            => isset( $params['download_method'] ) && in_array( $params['download_method'], array( 'stream', 'redirect' ), true ) ? $params['download_method'] : 'stream',
			'cache_ttl'                  => isset( $params['cache_ttl'] ) ? max( 0, absint( $params['cache_ttl'] ) ) : 600,
			'chunk_size_mb'              => isset( $params['chunk_size_mb'] ) ? max( 1, min( 64, absint( $params['chunk_size_mb'] ) ) ) : 8,
			'enable_shared_drive'        => ! empty( $params['enable_shared_drive'] ),
			'track_downloads'            => ! empty( $params['track_downloads'] ),
			'delete_data_on_uninstall'   => ! empty( $params['delete_data_on_uninstall'] ),
			'error_message_disconnected' => isset( $params['error_message_disconnected'] ) ? sanitize_text_field( trim( $params['error_message_disconnected'] ) ) : ( isset( $current['error_message_disconnected'] ) ? $current['error_message_disconnected'] : __( 'Error accessing Google Drive file: Google Drive account is not connected.', 'drivevault-for-woocommerce' ) ),
		);

		update_option( DRIVEVAULT_OPTION_SETTINGS, $updated );

		return new WP_REST_Response( array(
			'success'  => true,
			'settings' => $updated,
			'message'  => __( 'Settings saved successfully.', 'drivevault-for-woocommerce' ),
		), 200 );
	}
}

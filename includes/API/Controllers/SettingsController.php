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
				'args'                => array(
					'client_id'                  => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'client_secret'              => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'download_method'            => array(
						'type'              => 'string',
						'enum'              => array( 'stream', 'redirect' ),
						'sanitize_callback' => 'sanitize_text_field',
					),
					'cache_ttl'                  => array(
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'chunk_size_mb'              => array(
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'enable_shared_drive'        => array(
						'type' => 'boolean',
					),
					'delete_data_on_uninstall'   => array(
						'type' => 'boolean',
					),
					'error_message_disconnected' => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			),
		) );
	}

	/**
	 * Admin permissions check.
	 */
	public function check_admin_permissions() {
		return current_user_can( 'manage_woocommerce' ) || current_user_can( 'manage_options' );
	}

	/**
	 * Shape settings array for API responses with capability flags and redirect URI.
	 *
	 * @param array $settings Raw options array.
	 * @return array
	 */
	public function format_settings_response( array $settings ) {
		$defaults = array(
			'client_id'                  => '',
			'client_secret'              => '',
			'download_method'            => 'redirect',
			'cache_ttl'                  => 600,
			'chunk_size_mb'              => 8,
			'enable_shared_drive'        => false,
			'delete_data_on_uninstall'   => false,
			'error_message_disconnected' => __( 'Error accessing Google Drive file: Google Drive account is not connected.', 'drivevault-for-woocommerce' ),
		);

		$merged = wp_parse_args( $settings, $defaults );

		$has_client_secret                         = ! empty( $merged['client_secret'] );
		$response_data                             = $merged;
		$response_data['has_client_secret']         = $has_client_secret;
		if ( $has_client_secret ) {
			$response_data['client_secret'] = '••••••••••••••••••••';
		}
		$response_data['redirect_uri']               = $this->oauth->get_redirect_uri();
		$response_data['available_download_methods'] = apply_filters( 'drivevault_allowed_download_methods', array( 'redirect' ) );
		$has_shared_drive_support                   = (bool) apply_filters( 'drivevault_has_shared_drive_support', false );
		$response_data['has_shared_drive_support']   = $has_shared_drive_support;
		if ( ! $has_shared_drive_support ) {
			$response_data['enable_shared_drive'] = false;
		}

		return $response_data;
	}

	/**
	 * Get plugin settings.
	 */
	public function get_settings( WP_REST_Request $request ) {
		$settings = get_option( DRIVEVAULT_OPTION_SETTINGS, array() );
		return new WP_REST_Response( $this->format_settings_response( $settings ), 200 );
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

		$allowed_methods          = apply_filters( 'drivevault_allowed_download_methods', array( 'redirect' ) );
		$has_shared_drive_support = (bool) apply_filters( 'drivevault_has_shared_drive_support', false );

		$existing_secret = isset( $current['client_secret'] ) ? $current['client_secret'] : '';
		if ( ! isset( $params['client_secret'] ) ) {
			$client_secret = $existing_secret;
		} else {
			$client_secret_input = sanitize_text_field( trim( $params['client_secret'] ) );
			if ( preg_match( '/^[•\*]+$/u', $client_secret_input ) ) {
				$client_secret = $existing_secret;
			} elseif ( '' === $client_secret_input ) {
				$client_secret = ( isset( $params['client_id'] ) && empty( $params['client_id'] ) ) ? '' : $existing_secret;
			} else {
				$client_secret = $client_secret_input;
			}
		}

		$updated = array(
			'client_id'                  => isset( $params['client_id'] ) ? sanitize_text_field( trim( $params['client_id'] ) ) : ( isset( $current['client_id'] ) ? $current['client_id'] : '' ),
			'client_secret'              => $client_secret,
			'download_method'            => isset( $params['download_method'] ) && in_array( $params['download_method'], $allowed_methods, true ) ? $params['download_method'] : 'redirect',
			'cache_ttl'                  => isset( $params['cache_ttl'] ) ? max( 0, absint( $params['cache_ttl'] ) ) : 600,
			'chunk_size_mb'              => isset( $params['chunk_size_mb'] ) ? max( 1, min( 64, absint( $params['chunk_size_mb'] ) ) ) : 8,
			'enable_shared_drive'        => $has_shared_drive_support && ! empty( $params['enable_shared_drive'] ),
			'delete_data_on_uninstall'   => ! empty( $params['delete_data_on_uninstall'] ),
			'error_message_disconnected' => isset( $params['error_message_disconnected'] ) ? sanitize_text_field( trim( $params['error_message_disconnected'] ) ) : ( isset( $current['error_message_disconnected'] ) ? $current['error_message_disconnected'] : __( 'Error accessing Google Drive file: Google Drive account is not connected.', 'drivevault-for-woocommerce' ) ),
		);

		update_option( DRIVEVAULT_OPTION_SETTINGS, $updated, false );

		return new WP_REST_Response( array(
			'success'  => true,
			'settings' => $this->format_settings_response( $updated ),
			'message'  => __( 'Settings saved successfully.', 'drivevault-for-woocommerce' ),
		), 200 );
	}
}

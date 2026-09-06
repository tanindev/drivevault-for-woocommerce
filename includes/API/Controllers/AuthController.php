<?php
namespace DriveVault\API\Controllers;

use DriveVault\Services\OAuthManager;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * REST API Auth Controller.
 */
class AuthController {

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
	 * Register auth routes.
	 */
	public function register_routes() {
		register_rest_route( self::NAMESPACE, '/auth/status', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_status' ),
			'permission_callback' => array( $this, 'check_read_permissions' ),
		) );

		register_rest_route( self::NAMESPACE, '/auth/url', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_auth_url' ),
			'permission_callback' => array( $this, 'check_admin_permissions' ),
		) );

		register_rest_route( self::NAMESPACE, '/auth/callback', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'handle_callback' ),
			'permission_callback' => array( $this, 'check_admin_permissions' ),
			'args'                => array(
				'code'  => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'state' => array(
					'required'          => false,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
		) );

		register_rest_route( self::NAMESPACE, '/auth/disconnect', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'disconnect' ),
			'permission_callback' => array( $this, 'check_admin_permissions' ),
		) );
	}

	/**
	 * Permissions: Manage WooCommerce / Settings.
	 */
	public function check_admin_permissions() {
		return current_user_can( 'manage_woocommerce' ) || current_user_can( 'manage_options' );
	}

	/**
	 * Permissions: Read status & browse drive (allowed for product editors and admins).
	 */
	public function check_read_permissions() {
		return current_user_can( 'edit_products' ) || current_user_can( 'manage_woocommerce' ) || current_user_can( 'manage_options' );
	}

	/**
	 * Get connection status.
	 */
	public function get_status( WP_REST_Request $request ) {
		return new WP_REST_Response( $this->oauth->get_status(), 200 );
	}

	/**
	 * Get Google OAuth Auth URL.
	 */
	public function get_auth_url( WP_REST_Request $request ) {
		$auth_url = $this->oauth->get_auth_url();
		if ( is_wp_error( $auth_url ) ) {
			return $auth_url;
		}

		return new WP_REST_Response( array( 'url' => $auth_url ), 200 );
	}

	/**
	 * Handle OAuth authorization code exchange.
	 */
	public function handle_callback( WP_REST_Request $request ) {
		$code   = $request->get_param( 'code' );
		$state  = $request->get_param( 'state' );
		$result = $this->oauth->handle_auth_code( $code, $state );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response( array(
			'success' => true,
			'status'  => $this->oauth->get_status(),
			'message' => __( 'Google Drive account connected successfully!', 'drivevault-for-woocommerce' ),
		), 200 );
	}

	/**
	 * Disconnect Google Drive account.
	 */
	public function disconnect( WP_REST_Request $request ) {
		$this->oauth->disconnect();

		return new WP_REST_Response( array(
			'success' => true,
			'status'  => $this->oauth->get_status(),
			'message' => __( 'Google Drive disconnected.', 'drivevault-for-woocommerce' ),
		), 200 );
	}
}

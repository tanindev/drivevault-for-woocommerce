<?php
namespace DriveVault\API\Controllers;

use DriveVault\Services\GoogleDriveClient;
use DriveVault\Services\OAuthManager;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * REST API Drive Files & Quota Controller.
 */
class DriveController {

	const NAMESPACE = 'drivevault/v1';

	/**
	 * Google Drive Client.
	 *
	 * @var GoogleDriveClient
	 */
	protected $drive;

	/**
	 * OAuth Manager.
	 *
	 * @var OAuthManager
	 */
	protected $oauth;

	/**
	 * Constructor.
	 *
	 * @param GoogleDriveClient $drive
	 * @param OAuthManager      $oauth
	 */
	public function __construct( GoogleDriveClient $drive, OAuthManager $oauth ) {
		$this->drive = $drive;
		$this->oauth = $oauth;
	}

	/**
	 * Register routes.
	 */
	public function register_routes() {
		register_rest_route( self::NAMESPACE, '/drive/files', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_files' ),
			'permission_callback' => array( $this, 'check_editor_permissions' ),
			'args'                => array(
				'folder_id'   => array(
					'default'           => 'root',
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'search'      => array(
					'default'           => '',
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'page_token'  => array(
					'default'           => '',
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'page_size'   => array(
					'default'           => 40,
					'type'              => 'integer',
					'sanitize_callback' => 'absint',
				),
				'filter_type' => array(
					'default'           => 'all',
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'refresh'     => array(
					'default'           => false,
					'type'              => 'boolean',
				),
			),
		) );

		register_rest_route( self::NAMESPACE, '/drive/quota', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_quota' ),
			'permission_callback' => array( $this, 'check_editor_permissions' ),
		) );

		register_rest_route( self::NAMESPACE, '/drive/flush-cache', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'flush_cache' ),
			'permission_callback' => array( $this, 'check_editor_permissions' ),
		) );
	}

	/**
	 * Check permissions.
	 */
	public function check_editor_permissions() {
		return current_user_can( 'edit_products' ) || current_user_can( 'manage_woocommerce' );
	}

	/**
	 * Get Google Drive files.
	 */
	public function get_files( WP_REST_Request $request ) {
		if ( ! $this->oauth->is_connected() ) {
			return new WP_Error( 'not_connected', __( 'Google Drive is not connected.', 'drivevault-for-woocommerce' ), array( 'status' => 400 ) );
		}

		$args = array(
			'folder_id'   => $request->get_param( 'folder_id' ),
			'search'      => $request->get_param( 'search' ),
			'page_token'  => $request->get_param( 'page_token' ),
			'page_size'   => $request->get_param( 'page_size' ),
			'filter_type' => $request->get_param( 'filter_type' ),
		);

		$force_refresh = (bool) $request->get_param( 'refresh' );

		$result = $this->drive->list_files( $args, $force_refresh );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response( $result, 200 );
	}

	/**
	 * Get Google Drive quota.
	 */
	public function get_quota( WP_REST_Request $request ) {
		if ( ! $this->oauth->is_connected() ) {
			return new WP_Error( 'not_connected', __( 'Google Drive is not connected.', 'drivevault-for-woocommerce' ), array( 'status' => 400 ) );
		}

		$result = $this->drive->get_about();
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response( $result, 200 );
	}

	/**
	 * Flush transient caches.
	 */
	public function flush_cache( WP_REST_Request $request ) {
		\DriveVault\Helpers\Cache::flush_all();

		return new WP_REST_Response( array(
			'success' => true,
			'message' => __( 'Cache cleared successfully.', 'drivevault-for-woocommerce' ),
		), 200 );
	}
}

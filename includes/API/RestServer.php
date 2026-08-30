<?php
namespace DriveVault\API;

use DriveVault\Services\OAuthManager;
use DriveVault\Services\GoogleDriveClient;

defined( 'ABSPATH' ) || exit;

/**
 * REST Server Controller.
 */
class RestServer {

	const NAMESPACE = 'drivevault/v1';

	/**
	 * Services.
	 */
	protected $oauth;
	protected $drive;

	/**
	 * Constructor.
	 *
	 * @param OAuthManager      $oauth
	 * @param GoogleDriveClient $drive
	 */
	public function __construct( OAuthManager $oauth, GoogleDriveClient $drive ) {
		$this->oauth = $oauth;
		$this->drive = $drive;
		$this->init_hooks();
	}

	/**
	 * Register REST hooks.
	 */
	private function init_hooks() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register all REST API routes.
	 */
	public function register_routes() {
		$auth_controller     = new Controllers\AuthController( $this->oauth );
		$drive_controller    = new Controllers\DriveController( $this->drive, $this->oauth );
		$settings_controller = new Controllers\SettingsController( $this->oauth );

		$auth_controller->register_routes();
		$drive_controller->register_routes();
		$settings_controller->register_routes();
	}
}

<?php
namespace DriveVault\Services;

defined( 'ABSPATH' ) || exit;

/**
 * Google OAuth 2.0 Token & Authentication Manager.
 */
class OAuthManager {

	const GOOGLE_AUTH_URL   = 'https://accounts.google.com/o/oauth2/v2/auth';
	const GOOGLE_TOKEN_URL  = 'https://oauth2.googleapis.com/token';
	const GOOGLE_REVOKE_URL = 'https://oauth2.googleapis.com/revoke';
	const GOOGLE_USERINFO   = 'https://www.googleapis.com/oauth2/v2/userinfo';

	const SCOPES = array(
		'https://www.googleapis.com/auth/drive.readonly',
		'https://www.googleapis.com/auth/userinfo.email',
		'https://www.googleapis.com/auth/userinfo.profile',
	);

	/**
	 * Get the OAuth Redirect URI.
	 *
	 * @return string
	 */
	public function get_redirect_uri() {
		return admin_url( 'admin.php?page=drivevault-settings' );
	}

	/**
	 * Get stored Google Cloud App credentials.
	 *
	 * @return array
	 */
	public function get_credentials() {
		$settings = get_option( DRIVEVAULT_OPTION_SETTINGS, array() );
		return array(
			'client_id'     => ! empty( $settings['client_id'] ) ? trim( $settings['client_id'] ) : '',
			'client_secret' => ! empty( $settings['client_secret'] ) ? trim( $settings['client_secret'] ) : '',
		);
	}

	/**
	 * Get stored tokens.
	 *
	 * @return array|false
	 */
	public function get_tokens() {
		return get_option( DRIVEVAULT_OPTION_TOKENS, false );
	}

	/**
	 * Check if Google Drive is currently connected.
	 *
	 * @return bool
	 */
	public function is_connected() {
		$tokens = $this->get_tokens();
		return ! empty( $tokens['access_token'] ) || ! empty( $tokens['refresh_token'] );
	}

	/**
	 * Generate OAuth 2.0 Authorization URL.
	 *
	 * @return string|\WP_Error
	 */
	public function get_auth_url() {
		$creds = $this->get_credentials();
		if ( empty( $creds['client_id'] ) || empty( $creds['client_secret'] ) ) {
			return new \WP_Error( 'missing_credentials', __( 'Google Client ID and Client Secret are required.', 'drivevault-for-woocommerce' ) );
		}

		$state = wp_create_nonce( 'drivevault_oauth_state' );

		$params = array(
			'client_id'             => $creds['client_id'],
			'redirect_uri'          => $this->get_redirect_uri(),
			'response_type'         => 'code',
			'scope'                 => implode( ' ', self::SCOPES ),
			'access_type'           => 'offline',
			'prompt'                => 'consent',
			'include_granted_scopes'=> 'true',
			'state'                 => $state,
		);

		return self::GOOGLE_AUTH_URL . '?' . http_build_query( $params, '', '&', PHP_QUERY_RFC3986 );
	}

	/**
	 * Exchange Authorization Code for Access & Refresh Tokens.
	 *
	 * @param string $code Authorization code from Google.
	 * @return array|\WP_Error
	 */
	public function handle_auth_code( $code ) {
		$creds = $this->get_credentials();
		if ( empty( $creds['client_id'] ) || empty( $creds['client_secret'] ) ) {
			return new \WP_Error( 'missing_credentials', __( 'Missing Google Client ID or Secret.', 'drivevault-for-woocommerce' ) );
		}

		$body = array(
			'code'          => $code,
			'client_id'     => $creds['client_id'],
			'client_secret' => $creds['client_secret'],
			'redirect_uri'  => $this->get_redirect_uri(),
			'grant_type'    => 'authorization_code',
		);

		$response = wp_remote_post( self::GOOGLE_TOKEN_URL, array(
			'body'    => $body,
			'timeout' => 30,
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $data['access_token'] ) ) {
			$error_desc = ! empty( $data['error_description'] ) ? $data['error_description'] : __( 'Failed to exchange authorization code.', 'drivevault-for-woocommerce' );
			return new \WP_Error( 'token_exchange_failed', $error_desc );
		}

		$tokens = array(
			'access_token'  => $data['access_token'],
			'refresh_token' => ! empty( $data['refresh_token'] ) ? $data['refresh_token'] : '',
			'expires_at'    => time() + ( isset( $data['expires_in'] ) ? (int) $data['expires_in'] : 3600 ),
			'created_at'    => time(),
			'scope'         => ! empty( $data['scope'] ) ? $data['scope'] : '',
		);

		// If refresh_token wasn't returned (e.g. re-auth), retain previous refresh_token if present.
		$existing_tokens = $this->get_tokens();
		if ( empty( $tokens['refresh_token'] ) && ! empty( $existing_tokens['refresh_token'] ) ) {
			$tokens['refresh_token'] = $existing_tokens['refresh_token'];
		}

		// Fetch user profile information.
		$user_info = $this->fetch_user_profile( $tokens['access_token'] );
		if ( ! is_wp_error( $user_info ) ) {
			$tokens['account_email']   = ! empty( $user_info['email'] ) ? $user_info['email'] : '';
			$tokens['account_name']    = ! empty( $user_info['name'] ) ? $user_info['name'] : '';
			$tokens['account_picture'] = ! empty( $user_info['picture'] ) ? $user_info['picture'] : '';
		}

		update_option( DRIVEVAULT_OPTION_TOKENS, $tokens );
		\DriveVault\Helpers\Cache::flush_all();

		return $tokens;
	}

	/**
	 * Fetch basic Google User Profile (email, name, picture).
	 *
	 * @param string $access_token
	 * @return array|\WP_Error
	 */
	public function fetch_user_profile( $access_token ) {
		$response = wp_remote_get( self::GOOGLE_USERINFO, array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $access_token,
			),
			'timeout' => 15,
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = wp_remote_retrieve_body( $response );
		return json_decode( $body, true );
	}

	/**
	 * Get a valid Access Token, automatically refreshing if expired.
	 *
	 * @return string|\WP_Error
	 */
	public function get_access_token() {
		$tokens = $this->get_tokens();
		if ( empty( $tokens ) || empty( $tokens['access_token'] ) ) {
			return new \WP_Error( 'not_connected', __( 'Google Drive account is not connected.', 'drivevault-for-woocommerce' ) );
		}

		// Buffer by 60 seconds.
		if ( ! empty( $tokens['expires_at'] ) && $tokens['expires_at'] > ( time() + 60 ) ) {
			return $tokens['access_token'];
		}

		// Token is expired, try to refresh.
		if ( empty( $tokens['refresh_token'] ) ) {
			return new \WP_Error( 'refresh_token_missing', __( 'Refresh token is missing. Please reconnect your Google Drive account.', 'drivevault-for-woocommerce' ) );
		}

		return $this->refresh_access_token( $tokens['refresh_token'] );
	}

	/**
	 * Refresh access token using refresh_token.
	 *
	 * @param string $refresh_token
	 * @return string|\WP_Error
	 */
	public function refresh_access_token( $refresh_token ) {
		$creds = $this->get_credentials();
		if ( empty( $creds['client_id'] ) || empty( $creds['client_secret'] ) ) {
			return new \WP_Error( 'missing_credentials', __( 'Missing Google Client ID or Secret.', 'drivevault-for-woocommerce' ) );
		}

		$body = array(
			'client_id'     => $creds['client_id'],
			'client_secret' => $creds['client_secret'],
			'refresh_token' => $refresh_token,
			'grant_type'    => 'refresh_token',
		);

		$response = wp_remote_post( self::GOOGLE_TOKEN_URL, array(
			'body'    => $body,
			'timeout' => 30,
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $data['access_token'] ) ) {
			$error_desc = ! empty( $data['error_description'] ) ? $data['error_description'] : __( 'Failed to refresh Google Drive token.', 'drivevault-for-woocommerce' );
			return new \WP_Error( 'token_refresh_failed', $error_desc );
		}

		$tokens = $this->get_tokens();
		$tokens['access_token'] = $data['access_token'];
		$tokens['expires_at']   = time() + ( isset( $data['expires_in'] ) ? (int) $data['expires_in'] : 3600 );
		if ( ! empty( $data['refresh_token'] ) ) {
			$tokens['refresh_token'] = $data['refresh_token'];
		}

		update_option( DRIVEVAULT_OPTION_TOKENS, $tokens );

		return $tokens['access_token'];
	}

	/**
	 * Disconnect and revoke Google account.
	 *
	 * @return bool
	 */
	public function disconnect() {
		$tokens = $this->get_tokens();
		if ( ! empty( $tokens['access_token'] ) ) {
			wp_remote_post( self::GOOGLE_REVOKE_URL, array(
				'body'    => array( 'token' => $tokens['access_token'] ),
				'timeout' => 15,
			) );
		}

		delete_option( DRIVEVAULT_OPTION_TOKENS );
		\DriveVault\Helpers\Cache::flush_all();

		return true;
	}

	/**
	 * Get current connection status details.
	 *
	 * @return array
	 */
	public function get_status() {
		$tokens    = $this->get_tokens();
		$connected = $this->is_connected();
		$creds     = $this->get_credentials();

		return array(
			'is_connected'    => $connected,
			'has_credentials' => ! empty( $creds['client_id'] ) && ! empty( $creds['client_secret'] ),
			'client_id'       => $creds['client_id'],
			'redirect_uri'    => $this->get_redirect_uri(),
			'account_email'   => $connected && ! empty( $tokens['account_email'] ) ? $tokens['account_email'] : '',
			'account_name'    => $connected && ! empty( $tokens['account_name'] ) ? $tokens['account_name'] : '',
			'account_picture' => $connected && ! empty( $tokens['account_picture'] ) ? $tokens['account_picture'] : '',
			'expires_at'      => $connected && ! empty( $tokens['expires_at'] ) ? $tokens['expires_at'] : 0,
		);
	}
}

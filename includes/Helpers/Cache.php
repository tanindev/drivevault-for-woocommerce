<?php
namespace DriveVault\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Cache Helper for Google Drive responses using Transients.
 */
class Cache {

	const PREFIX = 'drivevault_';

	/**
	 * Get cached data.
	 *
	 * @param string $key Cache key.
	 * @return mixed|false
	 */
	public static function get( $key ) {
		return get_transient( self::PREFIX . md5( $key ) );
	}

	/**
	 * Set cached data.
	 *
	 * @param string $key        Cache key.
	 * @param mixed  $data       Data to cache.
	 * @param int    $expiration Expiration in seconds (defaults to plugin setting or 600s).
	 * @return bool
	 */
	public static function set( $key, $data, $expiration = null ) {
		if ( null === $expiration ) {
			$settings = get_option( 'drivevault_settings', array() );
			$expiration = ! empty( $settings['cache_ttl'] ) ? absint( $settings['cache_ttl'] ) : 600;
		}

		if ( $expiration <= 0 ) {
			return false;
		}

		return set_transient( self::PREFIX . md5( $key ), $data, $expiration );
	}

	/**
	 * Delete a specific cached item.
	 *
	 * @param string $key Cache key.
	 * @return bool
	 */
	public static function delete( $key ) {
		return delete_transient( self::PREFIX . md5( $key ) );
	}

	/**
	 * Flush all plugin transients.
	 */
	public static function flush_all() {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$wpdb->esc_like( '_transient_' . self::PREFIX ) . '%',
				$wpdb->esc_like( '_transient_timeout_' . self::PREFIX ) . '%'
			)
		);
	}
}

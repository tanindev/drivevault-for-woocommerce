<?php
/**
 * Uninstall Handler.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// Clean up transients.
global $wpdb;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
		$wpdb->esc_like( '_transient_drivevault_' ) . '%',
		$wpdb->esc_like( '_transient_timeout_drivevault_' ) . '%'
	)
);

// Delete plugin options if configured.
$drivevault_settings = get_option( 'drivevault_settings', array() );
if ( ! empty( $drivevault_settings['delete_data_on_uninstall'] ) ) {
	delete_option( 'drivevault_settings' );
	delete_option( 'drivevault_google_tokens' );
}

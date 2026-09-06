<?php
/**
 * Download Error Page Template
 *
 * This template can be overridden by copying it to yourtheme/drivevault/download-error.php
 * or yourtheme/woocommerce/drivevault/download-error.php
 *
 * @package DriveVault
 * @version 1.0.0
 *
 * @var string $title       The error title.
 * @var string $message     The error message.
 * @var int    $status_code HTTP response status code.
 * @var string $site_name   The name of the site.
 * @var string $home_url    The home URL of the site.
 * @var string $account_url The URL to customer's account / downloads.
 */

defined( 'ABSPATH' ) || exit;

$drivevault_status_code = isset( $args['status_code'] ) ? (int) $args['status_code'] : 502;

if ( ! headers_sent() ) {
	status_header( $drivevault_status_code );
	header( 'Content-Type: text/html; charset=' . get_bloginfo( 'charset' ) );
}

$drivevault_site_name = isset( $args['site_name'] ) ? $args['site_name'] : ( ! empty( $site_name ) ? $site_name : get_bloginfo( 'name' ) );
$drivevault_home_url  = isset( $args['home_url'] ) ? $args['home_url'] : ( ! empty( $home_url ) ? $home_url : home_url( '/' ) );
$drivevault_title     = isset( $args['title'] ) ? $args['title'] : ( ! empty( $title ) ? $title : __( 'Download Unavailable', 'drivevault-for-woocommerce' ) );
$drivevault_message   = isset( $args['message'] ) ? $args['message'] : ( ! empty( $message ) ? $message : __( 'Error accessing Google Drive file: Google Drive account is not connected.', 'drivevault-for-woocommerce' ) );

$drivevault_account_url = isset( $args['account_url'] ) ? $args['account_url'] : ( ! empty( $account_url ) ? $account_url : '' );
if ( empty( $drivevault_account_url ) ) {
	$drivevault_account_url = function_exists( 'wc_get_account_endpoint_url' ) ? wc_get_account_endpoint_url( 'downloads' ) : '';
	if ( empty( $drivevault_account_url ) && function_exists( 'wc_get_page_permalink' ) ) {
		$drivevault_account_url = wc_get_page_permalink( 'myaccount' );
	}
	if ( empty( $drivevault_account_url ) ) {
		$drivevault_account_url = $drivevault_home_url;
	}
}

// Enqueue dedicated stylesheet via WordPress queue.
wp_enqueue_style(
	'drivevault-download-error',
	DRIVEVAULT_ASSETS_URL . 'css/download-error.css',
	array(),
	DRIVEVAULT_VERSION
);
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="robots" content="noindex, nofollow">
	<title><?php echo esc_html( $drivevault_title . ' - ' . $drivevault_site_name ); ?></title>
	<?php
	wp_print_styles( 'drivevault-download-error' );
	do_action( 'drivevault_download_error_head' );
	?>
</head>
<body class="drivevault-error-page">
	<?php do_action( 'drivevault_before_download_error_card' ); ?>

	<div class="dv-error-card">
		<a href="<?php echo esc_url( $drivevault_home_url ); ?>" class="dv-brand">
			<?php echo esc_html( $drivevault_site_name ); ?>
		</a>

		<div class="dv-icon-wrap" aria-hidden="true">
			<svg viewBox="0 0 24 24">
				<path d="M19.35 10.04C18.67 6.59 15.64 4 12 4c-1.48 0-2.85.43-4.01 1.17l1.46 1.46C10.21 6.23 11.08 6 12 6c3.04 0 5.6 2.08 6.23 5.03l.36 1.7 1.71.18c1.55.17 2.7 1.49 2.7 3.09 0 .6-.18 1.16-.48 1.63l1.47 1.47c.64-.89 1.01-1.95 1.01-3.1 0-2.6-1.92-4.73-4.47-4.96zM3.41 1.86L2 3.27l4.08 4.08C3.84 8.23 2 10.87 2 14c0 3.31 2.69 6 6 6h11.73l2 2 1.41-1.41L3.41 1.86zM8 18c-2.21 0-4-1.79-4-4 0-1.78 1.17-3.29 2.79-3.83l6.04 6.04L8 18z"/>
			</svg>
		</div>

		<h1 class="dv-title"><?php echo esc_html( $drivevault_title ); ?></h1>

		<div class="dv-message-box">
			<?php echo esc_html( $drivevault_message ); ?>
		</div>

		<p class="dv-help-text">
			<?php esc_html_e( 'Please check back shortly or contact store support if you need assistance.', 'drivevault-for-woocommerce' ); ?>
		</p>

		<div class="dv-actions">
			<a href="<?php echo esc_url( $drivevault_account_url ); ?>" class="dv-btn dv-btn-primary">
				<?php esc_html_e( 'My Downloads', 'drivevault-for-woocommerce' ); ?>
			</a>
			<a href="<?php echo esc_url( $drivevault_home_url ); ?>" class="dv-btn dv-btn-secondary">
				<?php esc_html_e( 'Return to Store', 'drivevault-for-woocommerce' ); ?>
			</a>
		</div>
	</div>

	<?php do_action( 'drivevault_after_download_error_card' ); ?>
	<?php do_action( 'drivevault_download_error_footer' ); ?>
</body>
</html>

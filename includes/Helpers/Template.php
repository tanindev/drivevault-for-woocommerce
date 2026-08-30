<?php
namespace DriveVault\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Template Loader Helper.
 */
class Template {

	/**
	 * Locate a template and return the path for inclusion.
	 *
	 * Checks in:
	 * 1. yourtheme/drivevault/{template_name}
	 * 2. yourtheme/woocommerce/drivevault/{template_name}
	 * 3. plugin/templates/{template_name}
	 *
	 * @param string $template_name Template name (e.g. 'download-error.php').
	 * @param string $template_path Optional relative theme folder path.
	 * @param string $default_path  Optional fallback default path.
	 * @return string
	 */
	public static function locate_template( $template_name, $template_path = '', $default_path = '' ) {
		if ( ! $template_path ) {
			$template_path = apply_filters( 'drivevault_template_path', 'drivevault/' );
		}

		if ( ! $default_path ) {
			$default_path = DRIVEVAULT_PATH . 'templates/';
		}

		// Look within theme directory.
		$template = locate_template(
			array(
				trailingslashit( $template_path ) . $template_name,
				'woocommerce/' . trailingslashit( $template_path ) . $template_name,
				$template_name,
			)
		);

		// Fallback to plugin default template.
		if ( ! $template ) {
			$template = trailingslashit( $default_path ) . $template_name;
		}

		return apply_filters( 'drivevault_locate_template', $template, $template_name, $template_path );
	}

	/**
	 * Retrieve and render a template part.
	 *
	 * @param string $template_name Template name.
	 * @param array  $args          Variables passed into template scope.
	 * @param string $template_path Custom theme template path.
	 * @param string $default_path  Custom plugin fallback path.
	 */
	public static function get_template( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
		if ( ! empty( $args ) && is_array( $args ) ) {
			extract( $args ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- Extracting template arguments is standard practice for WordPress template loaders.
		}

		$located = self::locate_template( $template_name, $template_path, $default_path );

		if ( ! file_exists( $located ) ) {
			return;
		}

		do_action( 'drivevault_before_template_part', $template_name, $template_path, $located, $args );

		include $located;

		do_action( 'drivevault_after_template_part', $template_name, $template_path, $located, $args );
	}
}

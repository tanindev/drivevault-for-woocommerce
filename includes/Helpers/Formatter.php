<?php
namespace DriveVault\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Formatter Helper.
 */
class Formatter {

	/**
	 * Human-readable file size.
	 *
	 * @param int|string $bytes File size in bytes.
	 * @param int        $decimals Decimal precision.
	 * @return string
	 */
	public static function format_bytes( $bytes, $decimals = 2 ) {
		$bytes = (float) $bytes;
		if ( $bytes <= 0 ) {
			return '0 B';
		}
		$units = array( 'B', 'KB', 'MB', 'GB', 'TB', 'PB' );
		$pow = floor( ( $bytes ? log( $bytes ) : 0 ) / log( 1024 ) );
		$pow = min( $pow, count( $units ) - 1 );
		$bytes /= pow( 1024, $pow );
		return round( $bytes, $decimals ) . ' ' . $units[ $pow ];
	}

	/**
	 * Map MIME type to friendly category / icon name.
	 *
	 * @param string $mime_type
	 * @return array
	 */
	public static function map_mime_type( $mime_type ) {
		if ( 'application/vnd.google-apps.folder' === $mime_type ) {
			return array(
				'type' => 'folder',
				'icon' => 'folder',
				'label'=> __( 'Folder', 'drivevault-for-woocommerce' ),
			);
		}

		if ( strpos( $mime_type, 'image/' ) === 0 ) {
			return array(
				'type' => 'image',
				'icon' => 'image',
				'label'=> __( 'Image', 'drivevault-for-woocommerce' ),
			);
		}

		if ( strpos( $mime_type, 'video/' ) === 0 ) {
			return array(
				'type' => 'video',
				'icon' => 'movie',
				'label'=> __( 'Video', 'drivevault-for-woocommerce' ),
			);
		}

		if ( strpos( $mime_type, 'audio/' ) === 0 ) {
			return array(
				'type' => 'audio',
				'icon' => 'music_note',
				'label'=> __( 'Audio', 'drivevault-for-woocommerce' ),
			);
		}

		if ( strpos( $mime_type, 'zip' ) !== false || strpos( $mime_type, 'compressed' ) !== false || strpos( $mime_type, 'tar' ) !== false || strpos( $mime_type, 'rar' ) !== false || strpos( $mime_type, '7z' ) !== false ) {
			return array(
				'type' => 'archive',
				'icon' => 'folder_zip',
				'label'=> __( 'Archive / ZIP', 'drivevault-for-woocommerce' ),
			);
		}

		if ( strpos( $mime_type, 'pdf' ) !== false ) {
			return array(
				'type' => 'pdf',
				'icon' => 'picture_as_pdf',
				'label'=> __( 'PDF Document', 'drivevault-for-woocommerce' ),
			);
		}

		if ( strpos( $mime_type, 'spreadsheet' ) !== false || strpos( $mime_type, 'excel' ) !== false || strpos( $mime_type, 'csv' ) !== false ) {
			return array(
				'type' => 'spreadsheet',
				'icon' => 'table_chart',
				'label'=> __( 'Spreadsheet', 'drivevault-for-woocommerce' ),
			);
		}

		if ( strpos( $mime_type, 'document' ) !== false || strpos( $mime_type, 'word' ) !== false || strpos( $mime_type, 'text/' ) === 0 ) {
			return array(
				'type' => 'document',
				'icon' => 'description',
				'label'=> __( 'Document', 'drivevault-for-woocommerce' ),
			);
		}

		return array(
			'type' => 'file',
			'icon' => 'insert_drive_file',
			'label'=> __( 'File', 'drivevault-for-woocommerce' ),
		);
	}
}

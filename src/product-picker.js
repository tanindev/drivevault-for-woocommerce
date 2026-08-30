import React, { useState, useEffect } from 'react';
import { render } from '@wordpress/element';
import store from './store'; // Initialize @wordpress/data store
import FilePickerModal from './components/FilePickerModal';
import './scss/icons.scss';
import './scss/picker.scss';

const DRIVE_SVG_ICON = `
<svg width="16" height="16" viewBox="0 0 87.3 78" style="vertical-align: middle; display: inline-block; flex-shrink: 0;" xmlns="http://www.w3.org/2000/svg">
  <path d="m6.6 66.85 3.85 6.65c.8 1.4 1.95 2.5 3.3 3.3l13.75-23.8h-27.5c0 1.55.4 3.1 1.2 4.5z" fill="#0066da"/>
  <path d="m43.65 25-13.75-23.8c-1.35.8-2.5 1.9-3.3 3.3l-25.4 44c-.8 1.4-1.2 2.95-1.2 4.5h27.5z" fill="#00ac47"/>
  <path d="m73.55 76.8c1.35-.8 2.5-1.9 3.3-3.3l1.6-2.75 7.65-13.25c.8-1.4 1.2-2.95 1.2-4.5h-27.502l5.852 11.5z" fill="#ea4335"/>
  <path d="m43.65 25 13.75-23.8c-1.35-.8-2.9-1.2-4.5-1.2h-18.5c-1.6 0-3.15.45-4.5 1.2z" fill="#00832d"/>
  <path d="m59.8 53h-32.3l-13.75 23.8c1.35.8 2.9 1.2 4.5 1.2h50.8c1.6 0 3.15-.45 4.5-1.2z" fill="#2684fc"/>
  <path d="m73.4 26.5-12.7-22c-.8-1.4-1.95-2.5-3.3-3.3l-13.75 23.8 16.15 28h27.45c0-1.55-.4-3.1-1.2-4.5z" fill="#ffba00"/>
</svg>
`;

const ProductPickerApp = () => {
	const [ isOpen, setIsOpen ] = useState( false );
	const [ targetContext, setTargetContext ] = useState( {
		type: 'simple',
		loop: null,
		buttonEl: null,
	} );

	useEffect( () => {
		// Injects "Add from Google Drive" button right next to WooCommerce's "Add File" button
		const injectButtons = () => {
			// 1. Simple product table
			const simpleTables = document.querySelectorAll(
				'.downloadable_files table, #general_product_data table.widefat'
			);
			simpleTables.forEach( ( table ) => {
				const insertBtn = table.querySelector(
					'a.insert, button.insert'
				);
				if (
					insertBtn &&
					! insertBtn.parentNode.querySelector(
						'.drivevault-open-picker-btn'
					)
				) {
					const btn = document.createElement( 'button' );
					btn.type = 'button';
					btn.className =
						'button drivevault-open-picker-btn drivevault-drive-btn';
					btn.setAttribute( 'data-target', 'simple' );
					btn.innerHTML = `${ DRIVE_SVG_ICON } <span>Add from Google Drive</span>`;
					btn.style.marginLeft = '8px';
					insertBtn.parentNode.insertBefore(
						btn,
						insertBtn.nextSibling
					);
				}
			} );

			// 2. Variable product variations tables
			const variationTables = document.querySelectorAll(
				'.woocommerce_variation .downloadable_files table, .woocommerce_variation table'
			);
			variationTables.forEach( ( table ) => {
				const insertBtn = table.querySelector(
					'a.insert, button.insert'
				);
				const variationWrapper = table.closest(
					'.woocommerce_variation'
				);
				const loop = variationWrapper
					? variationWrapper.getAttribute( 'rel' )
					: null;

				if (
					insertBtn &&
					! insertBtn.parentNode.querySelector(
						'.drivevault-open-picker-btn'
					)
				) {
					const btn = document.createElement( 'button' );
					btn.type = 'button';
					btn.className =
						'button drivevault-open-picker-btn drivevault-drive-btn';
					btn.setAttribute( 'data-target', 'variation' );
					if ( loop !== null ) {
						btn.setAttribute( 'data-loop', loop );
					}
					btn.innerHTML = `${ DRIVE_SVG_ICON } <span>Add from Google Drive</span>`;
					btn.style.marginLeft = '8px';
					insertBtn.parentNode.insertBefore(
						btn,
						insertBtn.nextSibling
					);
				}
			} );
		};

		injectButtons();

		// Listen for variations loaded or added in WooCommerce
		if ( window.jQuery ) {
			window
				.jQuery( document )
				.on(
					'woocommerce_variations_loaded woocommerce_variations_added woocommerce_variations_saved',
					injectButtons
				);
		}

		// Interval to ensure buttons are present after tab switches or dynamic loads
		const interval = setInterval( injectButtons, 1000 );

		// Click handler for picker buttons
		const handlePickerButtonClick = ( e ) => {
			const btn = e.target.closest( '.drivevault-open-picker-btn' );
			if ( ! btn ) return;
			e.preventDefault();
			e.stopPropagation();

			const type = btn.getAttribute( 'data-target' ) || 'simple';
			const loop = btn.getAttribute( 'data-loop' ) || null;

			setTargetContext( { type, loop, buttonEl: btn } );
			setIsOpen( true );
		};

		document.addEventListener( 'click', handlePickerButtonClick );

		return () => {
			clearInterval( interval );
			document.removeEventListener( 'click', handlePickerButtonClick );
		};
	}, [] );

	const handleInsertFiles = ( files ) => {
		if ( ! files || ! files.length ) return;

		const $ = window.jQuery;
		if ( ! $ ) {
			alert( 'jQuery is required to insert downloadable files.' );
			return;
		}

		// 1. Locate the table directly from the clicked button
		let $table;
		if ( targetContext.buttonEl ) {
			$table = $( targetContext.buttonEl ).closest( 'table' );
		}

		// Fallback lookups if not found from button
		if ( ! $table || ! $table.length ) {
			if (
				targetContext.type === 'variation' &&
				targetContext.loop !== null
			) {
				$table = $(
					`.woocommerce_variation[rel="${ targetContext.loop }"] .downloadable_files table, #variable_product_options .woocommerce_variation:eq(${ targetContext.loop }) .downloadable_files table`
				);
			} else {
				$table = $(
					'.downloadable_files table, #general_product_data .downloadable_files table, #general_product_data table.widefat'
				);
			}
		}

		if ( ! $table || ! $table.length ) {
			$table = $( '.downloadable_files table, table.widefat' ).first();
		}

		const $tbody = $table.find( 'tbody' );
		const $insertButton = $table.find( 'a.insert, button.insert' );

		// Retrieve row template from WooCommerce Add File button
		const rowTemplate =
			$insertButton.attr( 'data-row' ) || $insertButton.data( 'row' );

		files.forEach( ( file ) => {
			const fileName = file.name || 'Google Drive File';
			const fileUrl = file.download_url || `gdrive://file/${ file.id }`;

			// Check if there is an existing empty row in the table
			let $emptyRow = null;
			$tbody.find( 'tr' ).each( function () {
				const $r = $( this );
				const nameVal = $r
					.find(
						'input[name*="file_name"], .file_name input[type="text"]'
					)
					.val();
				const urlVal = $r
					.find(
						'input[name*="file_url"], .file_url input[type="text"]'
					)
					.val();
				if (
					( nameVal === '' || nameVal === undefined ) &&
					( urlVal === '' || urlVal === undefined ) &&
					! $emptyRow
				) {
					$emptyRow = $r;
				}
			} );

			if ( $emptyRow && $emptyRow.length ) {
				// Fill existing empty row
				const $nameInp = $emptyRow.find(
					'input[name*="file_name"], .file_name input[type="text"]'
				);
				const $urlInp = $emptyRow.find(
					'input[name*="file_url"], .file_url input[type="text"]'
				);
				$nameInp.val( fileName ).trigger( 'input' ).trigger( 'change' );
				$urlInp.val( fileUrl ).trigger( 'input' ).trigger( 'change' );
			} else if ( rowTemplate ) {
				// Instantiate and populate row directly from WooCommerce template
				const $row = $( rowTemplate );
				$row.find(
					'input[name*="file_name"], .file_name input[type="text"], input[name*="_file_names"]'
				).val( fileName );
				$row.find(
					'input[name*="file_url"], .file_url input[type="text"], input[name*="_file_urls"]'
				).val( fileUrl );
				$tbody.append( $row );
				$row.find( ':input' ).trigger( 'input' ).trigger( 'change' );
			} else if ( $insertButton.length ) {
				// Fallback to triggering native button click
				$insertButton.trigger( 'click' );
				const $newRow = $tbody.find( 'tr:last' );
				$newRow
					.find(
						'input[name*="file_name"], .file_name input[type="text"]'
					)
					.val( fileName )
					.trigger( 'input' )
					.trigger( 'change' );
				$newRow
					.find(
						'input[name*="file_url"], .file_url input[type="text"]'
					)
					.val( fileUrl )
					.trigger( 'input' )
					.trigger( 'change' );
			}
		} );

		$( document.body ).trigger( 'woocommerce_variations_saved' );
	};

	return (
		<FilePickerModal
			isOpen={ isOpen }
			onClose={ () => setIsOpen( false ) }
			onInsertFiles={ handleInsertFiles }
		/>
	);
};

// Mount to DOM in admin footer
const mountRoot = document.getElementById(
	'drivevault-product-picker-modal-root'
);
if ( mountRoot ) {
	render( <ProductPickerApp />, mountRoot );
}

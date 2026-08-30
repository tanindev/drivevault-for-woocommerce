import React, { useEffect, useRef } from 'react';
import { useSelect, useDispatch } from '@wordpress/data';
import { __, sprintf } from '@wordpress/i18n';
import DriveBreadcrumbs from './DriveBreadcrumbs';
import DriveSearchBar from './DriveSearchBar';
import DriveBrowser from './DriveBrowser';
import Icon from './common/Icon';

export const FilePickerModal = ( { isOpen, onClose, onInsertFiles } ) => {
	const selectedFiles = useSelect(
		( select ) => select( 'drivevault/drive' )?.getSelectedFiles?.() || [],
		[]
	);
	const connectionStatus = useSelect(
		( select ) =>
			select( 'drivevault/drive' )?.getConnectionStatus?.() || {
				is_connected: false,
			},
		[]
	);
	const { fetchFiles, clearSelection, fetchStatus } =
		useDispatch( 'drivevault/drive' ) || {};

	const onCloseRef = useRef( onClose );
	onCloseRef.current = onClose;

	// Handle ESC key to close
	useEffect( () => {
		if ( ! isOpen ) return;

		const handleKeyDown = ( e ) => {
			if ( e.key === 'Escape' ) {
				e.stopPropagation();
				onCloseRef.current?.();
			}
		};

		document.addEventListener( 'keydown', handleKeyDown );
		return () => document.removeEventListener( 'keydown', handleKeyDown );
	}, [ isOpen ] );

	// Initial load when opened
	useEffect( () => {
		if ( isOpen ) {
			if ( fetchStatus ) fetchStatus();
			if ( fetchFiles ) fetchFiles( { folderId: 'root', search: '' } );
			if ( clearSelection ) clearSelection();
		}
	}, [ isOpen ] );

	if ( ! isOpen ) return null;

	const handleBackdropClick = ( e ) => {
		if ( e.target === e.currentTarget ) {
			onClose();
		}
	};

	const handleInsert = () => {
		if ( selectedFiles.length > 0 && onInsertFiles ) {
			onInsertFiles( selectedFiles );
		}
		onClose();
	};

	const isConnected = connectionStatus?.is_connected;

	return (
		<div
			className="drivevault-modal-overlay"
			onClick={ handleBackdropClick }
			role="dialog"
			aria-modal="true"
		>
			<div
				className="drivevault-modal-window"
				onClick={ ( e ) => e.stopPropagation() }
			>
				{ /* Modal Header */ }
				<div className="drivevault-modal-header">
					<div className="drivevault-modal-header-left">
						<div className="drivevault-modal-header-icon">
							<Icon
								name="cloud_queue"
								size={ 20 }
								color="#1a73e8"
							/>
						</div>
						<div className="drivevault-modal-header-text">
							<h2>
								{ __(
									'Select Downloadable Files from Google Drive',
									'drivevault-for-woocommerce'
								) }
							</h2>
						</div>
					</div>

					<button
						type="button"
						className="drivevault-modal-close-btn"
						onClick={ onClose }
						aria-label={ __(
							'Close modal',
							'drivevault-for-woocommerce'
						) }
					>
						<Icon name="close" size={ 20 } />
					</button>
				</div>

				{ /* Modal Content / Body */ }
				{ ! isConnected ? (
					<div className="drivevault-modal-not-connected-view">
						<div className="drivevault-not-connected-box">
							<div className="drivevault-not-connected-icon">
								<Icon
									name="cloud_off"
									size={ 44 }
									color="#d93025"
								/>
							</div>
							<h3>
								{ __(
									'Google Drive is Not Connected',
									'drivevault-for-woocommerce'
								) }
							</h3>
							<p>
								{ __(
									'Please configure your Google Cloud API credentials and connect your account in the plugin settings to browse files.',
									'drivevault-for-woocommerce'
								) }
							</p>
							<a
								href="admin.php?page=drivevault-settings"
								target="_blank"
								rel="noreferrer"
								className="drivevault-btn drivevault-btn--primary"
							>
								<Icon name="settings" size={ 18 } />
								<span>
									{ __(
										'Go to Google Drive Settings',
										'drivevault-for-woocommerce'
									) }
								</span>
							</a>
						</div>
					</div>
				) : (
					<div className="drivevault-modal-layout-container">
						{ /* Fixed Subheader Toolbar (Breadcrumbs + Search) */ }
						<div className="drivevault-modal-toolbar">
							<DriveBreadcrumbs />
							<DriveSearchBar />
						</div>

						{ /* Scrollable File List */ }
						<div className="drivevault-modal-content-area">
							<DriveBrowser />
						</div>

						{ /* Fixed Bottom Action Footer */ }
						<div className="drivevault-modal-footer">
							<div className="drivevault-modal-footer-left">
								{ selectedFiles.length > 0 ? (
									<div className="drivevault-selection-pill">
										<span className="drivevault-selection-badge">
											{ selectedFiles.length }
										</span>
										<span className="drivevault-selection-label">
											{ sprintf(
												/* translators: %d: count */
												__(
													'%d file(s) selected',
													'drivevault-for-woocommerce'
												),
												selectedFiles.length
											) }
										</span>
										<button
											type="button"
											className="drivevault-selection-clear-btn"
											onClick={ clearSelection }
											title={ __(
												'Clear selection',
												'drivevault-for-woocommerce'
											) }
										>
											<Icon name="close" size={ 13 } />
											<span>
												{ __(
													'Clear',
													'drivevault-for-woocommerce'
												) }
											</span>
										</button>
									</div>
								) : (
									<span className="drivevault-no-selection-hint">
										<Icon
											name="info"
											size={ 16 }
											color="#94a3b8"
										/>
										{ __(
											'Click on items to select files for download',
											'drivevault-for-woocommerce'
										) }
									</span>
								) }
							</div>

							<div className="drivevault-modal-footer-right">
								<button
									type="button"
									className="drivevault-btn drivevault-btn--secondary"
									onClick={ onClose }
								>
									{ __(
										'Cancel',
										'drivevault-for-woocommerce'
									) }
								</button>

								<button
									type="button"
									className="drivevault-btn drivevault-btn--primary"
									onClick={ handleInsert }
									disabled={ selectedFiles.length === 0 }
								>
									<Icon name="add" size={ 17 } />
									<span>
										{ selectedFiles.length > 0
											? sprintf(
													/* translators: %d: count */
													__(
														'Insert %d File(s)',
														'drivevault-for-woocommerce'
													),
													selectedFiles.length
											  )
											: __(
													'Insert Selected',
													'drivevault-for-woocommerce'
											  ) }
									</span>
								</button>
							</div>
						</div>
					</div>
				) }
			</div>
		</div>
	);
};

export default FilePickerModal;

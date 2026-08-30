import React from 'react';
import { useSelect, useDispatch } from '@wordpress/data';
import { Spinner, CheckboxControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import FileIcon from './common/FileIcon';
import Icon from './common/Icon';

export const DriveBrowser = ( { allowMultiSelect = true } ) => {
	const files = useSelect(
		( select ) => select( 'drivevault/drive' )?.getFiles?.() || [],
		[]
	);
	const isLoading = useSelect(
		( select ) => select( 'drivevault/drive' )?.isLoading?.() || false,
		[]
	);
	const error = useSelect(
		( select ) => select( 'drivevault/drive' )?.getError?.() || null,
		[]
	);
	const selectedFiles = useSelect(
		( select ) => select( 'drivevault/drive' )?.getSelectedFiles?.() || [],
		[]
	);
	const { setCurrentFolder, toggleSelectFile, fetchFiles } =
		useDispatch( 'drivevault/drive' ) || {};

	const isSelected = ( fileId ) =>
		selectedFiles.some( ( f ) => f.id === fileId );

	const handleFolderOpen = ( folder ) => {
		if ( setCurrentFolder ) setCurrentFolder( folder.id, folder.name );
		if ( fetchFiles ) fetchFiles( { folderId: folder.id, search: '' } );
	};

	const handleRowClick = ( file ) => {
		if ( file.is_folder ) {
			handleFolderOpen( file );
		} else {
			if ( toggleSelectFile ) toggleSelectFile( file );
		}
	};

	if ( isLoading ) {
		return (
			<div className="drivevault-browser-loading">
				<Spinner />
				<p>
					{ __(
						'Loading Google Drive files...',
						'drivevault-for-woocommerce'
					) }
				</p>
			</div>
		);
	}

	if ( error ) {
		return (
			<div className="drivevault-browser-error">
				<Icon name="error" size={ 32 } color="#d63638" />
				<p className="drivevault-error-title">
					{ __(
						'Error loading files',
						'drivevault-for-woocommerce'
					) }
				</p>
				<p className="drivevault-error-msg">{ error }</p>
			</div>
		);
	}

	if ( ! files || files.length === 0 ) {
		return (
			<div className="drivevault-browser-empty">
				<Icon name="cloud_off" size={ 48 } color="#9aa0a6" />
				<p className="drivevault-empty-title">
					{ __(
						'No files or folders found',
						'drivevault-for-woocommerce'
					) }
				</p>
				<p className="drivevault-empty-desc">
					{ __(
						'This folder is empty or no files matched your search filter.',
						'drivevault-for-woocommerce'
					) }
				</p>
			</div>
		);
	}

	return (
		<div className="drivevault-browser-container">
			<table className="drivevault-files-table">
				<thead>
					<tr>
						<th className="drivevault-col-check"></th>
						<th className="drivevault-col-icon"></th>
						<th className="drivevault-col-name">
							{ __( 'Name', 'drivevault-for-woocommerce' ) }
						</th>
						<th className="drivevault-col-type">
							{ __( 'Type', 'drivevault-for-woocommerce' ) }
						</th>
						<th className="drivevault-col-size">
							{ __( 'Size', 'drivevault-for-woocommerce' ) }
						</th>
					</tr>
				</thead>
				<tbody>
					{ files.map( ( file ) => {
						const selected = isSelected( file.id );
						return (
							<tr
								key={ file.id }
								className={ `drivevault-file-row ${
									file.is_folder ? 'is-folder' : 'is-file'
								} ${ selected ? 'is-selected' : '' }` }
								onClick={ () => handleRowClick( file ) }
								onDoubleClick={ () =>
									file.is_folder && handleFolderOpen( file )
								}
							>
								<td
									className="drivevault-col-check"
									onClick={ ( e ) => e.stopPropagation() }
								>
									{ ! file.is_folder ? (
										<CheckboxControl
											checked={ selected }
											onChange={ () =>
												toggleSelectFile &&
												toggleSelectFile( file )
											}
											aria-label={ file.name }
										/>
									) : null }
								</td>
								<td className="drivevault-col-icon">
									<FileIcon
										type={ file.type }
										isFolder={ file.is_folder }
										mimeType={ file.mime_type }
										size={ 22 }
									/>
								</td>
								<td className="drivevault-col-name">
									<span
										className="drivevault-file-title"
										title={ file.name }
									>
										{ file.name }
									</span>
								</td>
								<td className="drivevault-col-type">
									<span className="drivevault-type-badge">
										{ file.type_label }
									</span>
								</td>
								<td className="drivevault-col-size">
									<span className="drivevault-size-text">
										{ file.formatted_size || '—' }
									</span>
								</td>
							</tr>
						);
					} ) }
				</tbody>
			</table>
		</div>
	);
};

export default DriveBrowser;

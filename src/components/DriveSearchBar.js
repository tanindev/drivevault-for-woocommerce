import React, { useState, useEffect } from 'react';
import { useSelect, useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import Icon from './common/Icon';

export const DriveSearchBar = () => {
	const [ localSearch, setLocalSearch ] = useState( '' );
	const filterType = useSelect(
		( select ) => select( 'drivevault/drive' )?.getFilterType?.() || 'all',
		[]
	);
	const currentFolderId = useSelect(
		( select ) =>
			select( 'drivevault/drive' )?.getCurrentFolderId?.() || 'root',
		[]
	);
	const { setSearchQuery, setFilterType, fetchFiles } =
		useDispatch( 'drivevault/drive' ) || {};

	useEffect( () => {
		const timer = setTimeout( () => {
			if ( setSearchQuery ) setSearchQuery( localSearch );
			if ( fetchFiles )
				fetchFiles( {
					search: localSearch,
					folderId: currentFolderId,
					filterType,
				} );
		}, 400 );

		return () => clearTimeout( timer );
	}, [ localSearch ] );

	const handleFilterChange = ( newType ) => {
		if ( setFilterType ) setFilterType( newType );
		if ( fetchFiles )
			fetchFiles( {
				filterType: newType,
				search: localSearch,
				folderId: currentFolderId,
			} );
	};

	return (
		<div className="drivevault-search-bar">
			<div className="drivevault-search-input-wrap">
				<Icon
					name="search"
					size={ 17 }
					className="drivevault-search-icon"
				/>
				<input
					type="text"
					className="drivevault-search-field"
					value={ localSearch }
					onChange={ ( e ) => setLocalSearch( e.target.value ) }
					placeholder={ __(
						'Search files in Google Drive...',
						'drivevault-for-woocommerce'
					) }
				/>
				{ localSearch && (
					<button
						type="button"
						className="drivevault-search-clear"
						onClick={ () => setLocalSearch( '' ) }
						title={ __(
							'Clear search',
							'drivevault-for-woocommerce'
						) }
					>
						<Icon name="close" size={ 14 } />
					</button>
				) }
			</div>

			<div className="drivevault-filter-select-wrap">
				<select
					className="drivevault-filter-select-field"
					value={ filterType }
					onChange={ ( e ) => handleFilterChange( e.target.value ) }
				>
					<option value="all">
						{ __( 'All Items', 'drivevault-for-woocommerce' ) }
					</option>
					<option value="files">
						{ __( 'Files Only', 'drivevault-for-woocommerce' ) }
					</option>
					<option value="folders">
						{ __( 'Folders Only', 'drivevault-for-woocommerce' ) }
					</option>
				</select>
			</div>
		</div>
	);
};

export default DriveSearchBar;

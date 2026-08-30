import React from 'react';
import { useSelect, useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import Icon from './common/Icon';

export const DriveBreadcrumbs = ( { onRefresh } ) => {
	const breadcrumbs = useSelect(
		( select ) =>
			select( 'drivevault/drive' )?.getBreadcrumbs?.() || [
				{ id: 'root', name: 'My Drive' },
			],
		[]
	);
	const isLoading = useSelect(
		( select ) => select( 'drivevault/drive' )?.isLoading?.() || false,
		[]
	);
	const { navigateToBreadcrumb, fetchFiles } =
		useDispatch( 'drivevault/drive' ) || {};

	const handleBreadcrumbClick = ( index ) => {
		if ( navigateToBreadcrumb ) navigateToBreadcrumb( index );
		const target = breadcrumbs[ index ];
		if ( fetchFiles ) fetchFiles( { folderId: target.id, search: '' } );
	};

	const handleRefresh = () => {
		const current = breadcrumbs[ breadcrumbs.length - 1 ];
		if ( fetchFiles )
			fetchFiles( {
				folderId: current ? current.id : 'root',
				refresh: true,
			} );
		if ( onRefresh ) onRefresh();
	};

	return (
		<div className="drivevault-breadcrumbs-bar">
			<nav
				className="drivevault-breadcrumbs-list"
				aria-label={ __( 'Breadcrumbs', 'drivevault-for-woocommerce' ) }
			>
				{ breadcrumbs.map( ( crumb, idx ) => {
					const isLast = idx === breadcrumbs.length - 1;
					return (
						<React.Fragment key={ crumb.id || idx }>
							{ idx > 0 && (
								<span className="drivevault-breadcrumbs-separator">
									/
								</span>
							) }
							<button
								type="button"
								className={ `drivevault-breadcrumbs-item ${
									isLast ? 'is-active' : ''
								}` }
								onClick={ () =>
									! isLast && handleBreadcrumbClick( idx )
								}
								disabled={ isLast || isLoading }
							>
								{ idx === 0 && (
									<Icon
										name="cloud_queue"
										size={ 15 }
										style={ {
											marginRight: 4,
											color: isLast
												? '#334155'
												: '#1a73e8',
										} }
									/>
								) }
								{ crumb.name }
							</button>
						</React.Fragment>
					);
				} ) }
			</nav>

			<div className="drivevault-breadcrumbs-actions">
				<button
					type="button"
					className="drivevault-btn-icon"
					onClick={ handleRefresh }
					disabled={ isLoading }
					title={ __(
						'Refresh folder',
						'drivevault-for-woocommerce'
					) }
				>
					<Icon
						name="refresh"
						size={ 16 }
						className={ isLoading ? 'drivevault-spin' : '' }
					/>
				</button>
			</div>
		</div>
	);
};

export default DriveBreadcrumbs;

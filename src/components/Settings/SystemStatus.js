import React, { useEffect, useState } from 'react';
import { Spinner } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import Icon from '../common/Icon';

export const SystemStatus = () => {
	const quota = useSelect(
		( select ) => select( 'drivevault/drive' ).getQuota(),
		[]
	);
	const connectionStatus = useSelect(
		( select ) => select( 'drivevault/drive' ).getConnectionStatus(),
		[]
	);
	const { fetchQuota, createToast } = useDispatch( 'drivevault/drive' );
	const [ clearing, setClearing ] = useState( false );

	useEffect( () => {
		if ( connectionStatus?.is_connected ) {
			fetchQuota();
		}
	}, [ connectionStatus?.is_connected ] );

	const handleClearCache = async () => {
		setClearing( true );
		try {
			await apiFetch( {
				path: 'drivevault/v1/drive/flush-cache',
				method: 'POST',
			} );
			createToast(
				__(
					'Directory transient cache cleared successfully.',
					'drivevault-for-woocommerce'
				),
				'success'
			);
			fetchQuota();
		} catch ( err ) {
			createToast(
				err.message ||
					__(
						'Failed to clear cache.',
						'drivevault-for-woocommerce'
					),
				'error'
			);
		} finally {
			setClearing( false );
		}
	};

	const isConnected = connectionStatus?.is_connected;

	return (
		<div className="drivevault-settings-section">
			{ /* Storage Quota Card */ }
			<div className="drivevault-ui-card">
				<div className="drivevault-ui-card__header">
					<div className="drivevault-card-header-flex">
						<div>
							<h2>
								{ __(
									'Google Drive Storage & Quota',
									'drivevault-for-woocommerce'
								) }
							</h2>
							<p className="drivevault-card-header-sub">
								{ __(
									'Live overview of your Google Cloud storage usage and limits.',
									'drivevault-for-woocommerce'
								) }
							</p>
						</div>
						<button
							type="button"
							className="drivevault-btn drivevault-btn--secondary"
							onClick={ handleClearCache }
							disabled={ clearing }
						>
							{ clearing ? (
								<>
									<Spinner />
									<span>
										{ __(
											'Clearing...',
											'drivevault-for-woocommerce'
										) }
									</span>
								</>
							) : (
								<>
									<Icon
										name="cleaning_services"
										size={ 16 }
									/>
									<span>
										{ __(
											'Flush Cache',
											'drivevault-for-woocommerce'
										) }
									</span>
								</>
							) }
						</button>
					</div>
				</div>
				<div className="drivevault-ui-card__body">
					{ ! isConnected ? (
						<p className="drivevault-text-muted">
							{ __(
								'Connect your Google Drive account in API Settings to view storage quota and profile details.',
								'drivevault-for-woocommerce'
							) }
						</p>
					) : quota ? (
						<div className="drivevault-quota-wrapper">
							<div className="drivevault-quota-metrics">
								<div className="drivevault-quota-metric">
									<span className="drivevault-metric-label">
										{ __(
											'Space Used',
											'drivevault-for-woocommerce'
										) }
									</span>
									<span className="drivevault-metric-val">
										{ quota.usage_formatted }
									</span>
								</div>
								<div className="drivevault-quota-metric">
									<span className="drivevault-metric-label">
										{ __(
											'Total Storage',
											'drivevault-for-woocommerce'
										) }
									</span>
									<span className="drivevault-metric-val">
										{ quota.limit_formatted }
									</span>
								</div>
								<div className="drivevault-quota-metric">
									<span className="drivevault-metric-label">
										{ __(
											'Usage Percentage',
											'drivevault-for-woocommerce'
										) }
									</span>
									<span className="drivevault-metric-val">
										{ quota.percent_used }%
									</span>
								</div>
							</div>

							<div className="drivevault-progress-track">
								<div
									className="drivevault-progress-fill"
									style={ {
										width: `${ Math.min(
											100,
											quota.percent_used
										) }%`,
										backgroundColor:
											quota.percent_used > 85
												? '#d93025'
												: '#1a73e8',
									} }
								></div>
							</div>
						</div>
					) : (
						<div
							className="drivevault-quota-wrapper drivevault-skeleton-wrapper"
							aria-hidden="true"
						>
							<div className="drivevault-quota-metrics">
								<div className="drivevault-quota-metric drivevault-skeleton-card">
									<div className="drivevault-skeleton drivevault-skeleton--label"></div>
									<div className="drivevault-skeleton drivevault-skeleton--val"></div>
								</div>
								<div className="drivevault-quota-metric drivevault-skeleton-card">
									<div className="drivevault-skeleton drivevault-skeleton--label"></div>
									<div className="drivevault-skeleton drivevault-skeleton--val"></div>
								</div>
								<div className="drivevault-quota-metric drivevault-skeleton-card">
									<div className="drivevault-skeleton drivevault-skeleton--label"></div>
									<div className="drivevault-skeleton drivevault-skeleton--val"></div>
								</div>
							</div>

							<div className="drivevault-progress-track drivevault-skeleton-progress">
								<div className="drivevault-skeleton drivevault-skeleton--bar"></div>
							</div>
						</div>
					) }
				</div>
			</div>

			{ /* System Requirements & Environment */ }
			<div className="drivevault-ui-card drivevault-mt-4">
				<div className="drivevault-ui-card__header">
					<h2>
						{ __(
							'System Environment Check',
							'drivevault-for-woocommerce'
						) }
					</h2>
					<p className="drivevault-card-header-sub">
						{ __(
							'Server extensions and compatibility indicators.',
							'drivevault-for-woocommerce'
						) }
					</p>
				</div>
				<div className="drivevault-ui-card__body">
					<table className="drivevault-status-table">
						<tbody>
							<tr>
								<td>
									<strong>
										{ __(
											'cURL Extension',
											'drivevault-for-woocommerce'
										) }
									</strong>
								</td>
								<td>
									<span className="drivevault-tag drivevault-tag--success">
										<Icon name="check" size={ 14 } />{ ' ' }
										{ __(
											'Enabled (Required for Google Drive API)',
											'drivevault-for-woocommerce'
										) }
									</span>
								</td>
							</tr>
							<tr>
								<td>
									<strong>
										{ __(
											'OpenSSL / HTTPS',
											'drivevault-for-woocommerce'
										) }
									</strong>
								</td>
								<td>
									<span className="drivevault-tag drivevault-tag--success">
										<Icon name="check" size={ 14 } />{ ' ' }
										{ __(
											'Supported',
											'drivevault-for-woocommerce'
										) }
									</span>
								</td>
							</tr>
							<tr>
								<td>
									<strong>
										{ __(
											'WooCommerce Download Engine',
											'drivevault-for-woocommerce'
										) }
									</strong>
								</td>
								<td>
									<span className="drivevault-tag drivevault-tag--success">
										<Icon name="check" size={ 14 } />{ ' ' }
										{ __(
											'Active',
											'drivevault-for-woocommerce'
										) }
									</span>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	);
};

export default SystemStatus;

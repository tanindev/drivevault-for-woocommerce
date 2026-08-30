import React, { useState, useEffect } from 'react';
import { render } from '@wordpress/element';
import { Button, Notice, Spinner } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

import store from './store';
import Tabs from './components/common/Tabs';
import ConnectionBadge from './components/common/ConnectionBadge';
import ApiSettings from './components/Settings/ApiSettings';
import DownloadSettings from './components/Settings/DownloadSettings';
import SystemStatus from './components/Settings/SystemStatus';
import Icon from './components/common/Icon';

import './scss/icons.scss';
import './scss/admin.scss';

const AdminApp = () => {
	const [ activeTab, setActiveTab ] = useState( 'api' );
	const [ localSettings, setLocalSettings ] = useState( null );
	const [ notice, setNotice ] = useState( null );
	const [ isProcessingAuth, setIsProcessingAuth ] = useState( false );

	const connectionStatus = useSelect(
		( select ) => select( 'drivevault/drive' ).getConnectionStatus(),
		[]
	);
	const settings = useSelect(
		( select ) => select( 'drivevault/drive' ).getSettings(),
		[]
	);
	const isLoading = useSelect(
		( select ) => select( 'drivevault/drive' ).isLoading(),
		[]
	);

	const { fetchSettings, saveSettings, fetchStatus, setConnectionStatus } =
		useDispatch( 'drivevault/drive' );

	// Check URL for OAuth callback code
	useEffect( () => {
		fetchStatus();
		fetchSettings();

		const urlParams = new URLSearchParams( window.location.search );
		const code = urlParams.get( 'code' );

		if ( code ) {
			setIsProcessingAuth( true );
			apiFetch( {
				path: 'drivevault/v1/auth/callback',
				method: 'POST',
				data: { code },
			} )
				.then( ( res ) => {
					setNotice( {
						status: 'success',
						message:
							res.message ||
							__(
								'Connected to Google Drive successfully!',
								'drivevault-for-woocommerce'
							),
					} );
					fetchStatus();
					// Clean up URL
					const cleanUrl =
						window.location.origin +
						window.location.pathname +
						'?page=drivevault-settings';
					window.history.replaceState( {}, document.title, cleanUrl );
				} )
				.catch( ( err ) => {
					setNotice( {
						status: 'error',
						message:
							err.message ||
							__(
								'Failed to complete Google authentication.',
								'drivevault-for-woocommerce'
							),
					} );
				} )
				.finally( () => {
					setIsProcessingAuth( false );
				} );
		}
	}, [] );

	useEffect( () => {
		if ( settings ) {
			setLocalSettings( settings );
		}
	}, [ settings ] );

	const handleFieldChange = ( key, value ) => {
		setLocalSettings( ( prev ) => ( {
			...prev,
			[ key ]: value,
		} ) );
	};

	const handleSave = async () => {
		if ( ! localSettings ) return;
		setNotice( null );
		try {
			const res = await saveSettings( localSettings );
			setNotice( {
				status: 'success',
				message:
					res.message ||
					__(
						'Settings saved successfully.',
						'drivevault-for-woocommerce'
					),
			} );
		} catch ( err ) {
			setNotice( {
				status: 'error',
				message:
					err.message ||
					__(
						'Failed to save settings.',
						'drivevault-for-woocommerce'
					),
			} );
		}
	};

	const tabs = [
		{
			key: 'api',
			title: __( 'API & Connection', 'drivevault-for-woocommerce' ),
			icon: <Icon name="key" size={ 17 } />,
		},
		{
			key: 'downloads',
			title: __( 'Download Settings', 'drivevault-for-woocommerce' ),
			icon: <Icon name="cloud_download" size={ 17 } />,
		},
		{
			key: 'status',
			title: __( 'Quota & Status', 'drivevault-for-woocommerce' ),
			icon: <Icon name="pie_chart" size={ 17 } />,
		},
	];

	return (
		<div className="drivevault-app">
			{ /* Top Header Card */ }
			<header className="drivevault-header">
				<div className="drivevault-header-left">
					<div className="drivevault-logo-icon">
						<Icon name="cloud_queue" size={ 30 } color="#1a73e8" />
					</div>
					<div className="drivevault-header-titles">
						<h1>
							{ __(
								'WooCommerce Google Drive Integration',
								'drivevault-for-woocommerce'
							) }
						</h1>
						<p className="drivevault-subtitle">
							{ __(
								'Attach Google Drive files as downloadable products with high-speed secure streaming.',
								'drivevault-for-woocommerce'
							) }
						</p>
					</div>
				</div>
				<div className="drivevault-header-right">
					<ConnectionBadge
						isConnected={ connectionStatus?.is_connected }
						accountEmail={ connectionStatus?.account_email }
					/>
				</div>
			</header>

			{ /* Main Settings Body */ }
			<div className="drivevault-body-card">
				{ /* Navigation Tabs Bar */ }
				<div className="drivevault-tabs-header-bar">
					<Tabs
						tabs={ tabs }
						active={ activeTab }
						onChange={ setActiveTab }
					/>
				</div>

				{ isProcessingAuth && (
					<div className="drivevault-loading-overlay">
						<Spinner />
						<p>
							{ __(
								'Authenticating with Google Cloud...',
								'drivevault-for-woocommerce'
							) }
						</p>
					</div>
				) }

				{ notice && (
					<div className="drivevault-notice-wrapper">
						<Notice
							status={ notice.status }
							onRemove={ () => setNotice( null ) }
							className="drivevault-app-notice"
						>
							<p>{ notice.message }</p>
						</Notice>
					</div>
				) }

				{ /* Tab Contents */ }
				<div className="drivevault-tab-content-wrapper">
					{ activeTab === 'api' && (
						<ApiSettings
							settings={ localSettings }
							onChange={ handleFieldChange }
							onSave={ handleSave }
							connectionStatus={ connectionStatus }
							onStatusChange={ fetchStatus }
						/>
					) }

					{ activeTab === 'downloads' && (
						<DownloadSettings
							settings={ localSettings }
							onChange={ handleFieldChange }
						/>
					) }

					{ activeTab === 'status' && <SystemStatus /> }
				</div>

				{ /* Action Save Bar */ }
				<div className="drivevault-save-bar">
					<button
						type="button"
						className="drivevault-btn drivevault-btn--primary"
						onClick={ handleSave }
						disabled={ isLoading || ! localSettings }
					>
						{ isLoading ? (
							<>
								<Spinner />
								<span>
									{ __(
										'Saving...',
										'drivevault-for-woocommerce'
									) }
								</span>
							</>
						) : (
							<>
								<Icon name="check" size={ 17 } />
								<span>
									{ __(
										'Save Settings',
										'drivevault-for-woocommerce'
									) }
								</span>
							</>
						) }
					</button>
				</div>
			</div>
		</div>
	);
};

const root = document.getElementById( 'drivevault-admin-root' );
if ( root ) {
	render( <AdminApp />, root );
}

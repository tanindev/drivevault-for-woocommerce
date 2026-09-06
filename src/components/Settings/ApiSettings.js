import React, { useState } from 'react';
import {
	TextControl,
	Button,
	ClipboardButton,
	ExternalLink,
	Spinner,
} from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import Icon from '../common/Icon';

export const ApiSettings = ( {
	settings,
	onChange,
	onSave,
	connectionStatus,
	onStatusChange,
} ) => {
	const [ copied, setCopied ] = useState( false );
	const [ connecting, setConnecting ] = useState( false );
	const [ disconnecting, setDisconnecting ] = useState( false );
	const [ showInstructions, setShowInstructions ] = useState( false );

	const { createToast } = useDispatch( 'drivevault/drive' );

	const redirectUri =
		window.location.origin +
		window.location.pathname +
		'?page=drivevault-settings';

	const handleConnect = async () => {
		setConnecting( true );
		try {
			await onSave();
			const response = await apiFetch( {
				path: 'drivevault/v1/auth/url',
			} );
			if ( response && response.url ) {
				window.location.href = response.url;
			}
		} catch ( err ) {
			createToast(
				err.message ||
					__(
						'Failed to generate Google Auth URL. Ensure Client ID & Secret are saved.',
						'drivevault-for-woocommerce'
					),
				'error'
			);
			setConnecting( false );
		}
	};

	const handleDisconnect = async () => {
		if (
			! window.confirm(
				__(
					'Are you sure you want to disconnect your Google Drive account?',
					'drivevault-for-woocommerce'
				)
			)
		) {
			return;
		}

		setDisconnecting( true );
		try {
			await apiFetch( {
				path: 'drivevault/v1/auth/disconnect',
				method: 'POST',
			} );
			onStatusChange();
			createToast(
				__(
					'Google Drive disconnected successfully.',
					'drivevault-for-woocommerce'
				),
				'success'
			);
		} catch ( err ) {
			createToast(
				err.message ||
					__(
						'Failed to disconnect Google Drive.',
						'drivevault-for-woocommerce'
					),
				'error'
			);
		} finally {
			setDisconnecting( false );
		}
	};

	const isConnected = connectionStatus?.is_connected;

	return (
		<div className="drivevault-settings-section">
			{ /* Modern Connection Banner Card */ }
			<div
				className={ `drivevault-ui-card drivevault-auth-card ${
					isConnected ? 'is-connected' : ''
				}` }
			>
				<div className="drivevault-auth-banner">
					<div className="drivevault-auth-info">
						<div className="drivevault-auth-logo">
							<Icon
								name="cloud_queue"
								size={ 32 }
								color={ isConnected ? '#137333' : '#1a73e8' }
							/>
						</div>
						<div className="drivevault-auth-text">
							<h3>
								{ __(
									'Google Drive Account Connection',
									'drivevault-for-woocommerce'
								) }
							</h3>
							{ isConnected ? (
								<p className="drivevault-connected-email">
									{ __(
										'Connected Account:',
										'drivevault-for-woocommerce'
									) }{ ' ' }
									<strong className="drivevault-email-tag">
										{ connectionStatus.account_email ||
											connectionStatus.account_name ||
											__(
												'Active Account',
												'drivevault-for-woocommerce'
											) }
									</strong>
								</p>
							) : (
								<p className="drivevault-auth-desc">
									{ __(
										'Connect your Google Cloud project to allow browsing and attaching Drive files to WooCommerce products.',
										'drivevault-for-woocommerce'
									) }
								</p>
							) }
						</div>
					</div>

					<div className="drivevault-auth-actions">
						{ isConnected ? (
							<button
								type="button"
								className="drivevault-btn drivevault-btn--danger-outline"
								onClick={ handleDisconnect }
								disabled={ disconnecting }
							>
								{ disconnecting ? (
									<>
										<Spinner />
										<span>
											{ __(
												'Disconnecting...',
												'drivevault-for-woocommerce'
											) }
										</span>
									</>
								) : (
									<>
										<Icon name="link_off" size={ 17 } />
										<span>
											{ __(
												'Disconnect Account',
												'drivevault-for-woocommerce'
											) }
										</span>
									</>
								) }
							</button>
						) : (
							<button
								type="button"
								className="drivevault-btn drivevault-btn--google"
								onClick={ handleConnect }
								disabled={
									connecting ||
									! settings?.client_id ||
									( ! settings?.client_secret &&
										! settings?.has_client_secret )
								}
							>
								{ connecting ? (
									<>
										<Spinner />
										<span>
											{ __(
												'Connecting...',
												'drivevault-for-woocommerce'
											) }
										</span>
									</>
								) : (
									<>
										<Icon name="link" size={ 18 } />
										<span>
											{ __(
												'Authorize & Connect Google Drive',
												'drivevault-for-woocommerce'
											) }
										</span>
									</>
								) }
							</button>
						) }
					</div>
				</div>
			</div>

			{ /* Google Cloud API Credentials Card */ }
			<div className="drivevault-ui-card">
				<div className="drivevault-ui-card__header">
					<div className="drivevault-card-header-flex">
						<div>
							<h2>
								{ __(
									'Google Cloud API Credentials',
									'drivevault-for-woocommerce'
								) }
							</h2>
							<p className="drivevault-card-header-sub">
								{ __(
									'Configure OAuth 2.0 Client credentials from your Google Cloud Console project.',
									'drivevault-for-woocommerce'
								) }
							</p>
						</div>
						<button
							type="button"
							className="drivevault-btn drivevault-btn--ghost"
							onClick={ () =>
								setShowInstructions( ! showInstructions )
							}
						>
							<Icon
								name={
									showInstructions
										? 'visibility_off'
										: 'help_outline'
								}
								size={ 16 }
							/>
							<span>
								{ showInstructions
									? __(
											'Hide Setup Guide',
											'drivevault-for-woocommerce'
									  )
									: __(
											'View Setup Guide',
											'drivevault-for-woocommerce'
									  ) }
							</span>
						</button>
					</div>
				</div>

				<div className="drivevault-ui-card__body">
					{ showInstructions && (
						<div className="drivevault-instructions-box">
							<h4>
								{ __(
									'Step-by-Step Setup Guide:',
									'drivevault-for-woocommerce'
								) }
							</h4>
							<ol className="drivevault-guide-steps">
								<li>
									Go to the{ ' ' }
									<ExternalLink href="https://console.cloud.google.com/">
										Google Cloud Console
									</ExternalLink>{ ' ' }
									and create or select a project.
								</li>
								<li>
									Navigate to{ ' ' }
									<strong>
										APIs &amp; Services &gt; Library
									</strong>
									, search for{ ' ' }
									<strong>Google Drive API</strong>, and click{ ' ' }
									<strong>Enable</strong>.
								</li>
								<li>
									Go to{ ' ' }
									<strong>
										APIs &amp; Services &gt; OAuth consent
										screen
									</strong>
									, select <strong>External</strong>, and fill
									in the required app information.
								</li>
								<li>
									Under <strong>Scopes</strong>, add{ ' ' }
									<code>
										https://www.googleapis.com/auth/drive.readonly
									</code>
									.
								</li>
								<li>
									Go to{ ' ' }
									<strong>
										APIs &amp; Services &gt; Credentials
									</strong>
									, click{ ' ' }
									<strong>
										Create Credentials &gt; OAuth client ID
									</strong>
									.
								</li>
								<li>
									Select <strong>Web application</strong> as
									the Application type.
								</li>
								<li>
									Paste the following under{ ' ' }
									<strong>Authorized redirect URIs</strong>:
									<div className="drivevault-code-copy-row">
										<code>{ redirectUri }</code>
										<ClipboardButton
											text={ redirectUri }
											onCopy={ () => setCopied( true ) }
											onFinishCopy={ () =>
												setCopied( false )
											}
											variant="secondary"
											isSmall
										>
											{ copied
												? __(
														'Copied!',
														'drivevault-for-woocommerce'
												  )
												: __(
														'Copy URI',
														'drivevault-for-woocommerce'
												  ) }
										</ClipboardButton>
									</div>
								</li>
								<li>
									Copy your generated{ ' ' }
									<strong>Client ID</strong> and{ ' ' }
									<strong>Client Secret</strong> into the
									inputs below and click{ ' ' }
									<strong>
										Authorize &amp; Connect Google Drive
									</strong>{ ' ' }
									above.
								</li>
							</ol>
						</div>
					) }

					<div className="drivevault-form-fields">
						<div className="drivevault-form-group">
							<label className="drivevault-label">
								{ __(
									'Google Client ID',
									'drivevault-for-woocommerce'
								) }
								<span className="drivevault-required">*</span>
							</label>
							<input
								type="text"
								className="drivevault-input"
								value={ settings?.client_id || '' }
								onChange={ ( e ) =>
									onChange( 'client_id', e.target.value )
								}
								placeholder="e.g. 1234567890-abcdef.apps.googleusercontent.com"
							/>
							<span className="drivevault-help-text">
								{ __(
									'Your OAuth 2.0 Client ID generated in Google Cloud Console.',
									'drivevault-for-woocommerce'
								) }
							</span>
						</div>

						<div className="drivevault-form-group">
							<label className="drivevault-label">
								{ __(
									'Google Client Secret',
									'drivevault-for-woocommerce'
								) }
								<span className="drivevault-required">*</span>
							</label>
							<input
								type="password"
								className="drivevault-input"
								value={ settings?.client_secret || '' }
								onChange={ ( e ) =>
									onChange( 'client_secret', e.target.value )
								}
								onFocus={ ( e ) => {
									if (
										e.target.value &&
										e.target.value.includes( '•' )
									) {
										onChange( 'client_secret', '' );
									}
								} }
								onBlur={ ( e ) => {
									if (
										! e.target.value &&
										settings?.has_client_secret
									) {
										onChange(
											'client_secret',
											'••••••••••••••••••••'
										);
									}
								} }
								placeholder={
									settings?.has_client_secret
										? '••••••••••••••••••••'
										: __(
												'Enter your Google Client Secret',
												'drivevault-for-woocommerce'
										  )
								}
							/>
							<span className="drivevault-help-text">
								{ __(
									'Your OAuth 2.0 Client Secret generated in Google Cloud Console.',
									'drivevault-for-woocommerce'
								) }
							</span>
						</div>

						<div className="drivevault-form-group">
							<label className="drivevault-label">
								{ __(
									'Authorized Redirect URI',
									'drivevault-for-woocommerce'
								) }
							</label>
							<div className="drivevault-input-with-button">
								<input
									type="text"
									readOnly
									value={ redirectUri }
									className="drivevault-input is-readonly"
								/>
								<ClipboardButton
									text={ redirectUri }
									onCopy={ () => setCopied( true ) }
									onFinishCopy={ () => setCopied( false ) }
									className="drivevault-btn drivevault-btn--secondary"
								>
									<Icon
										name={
											copied ? 'check' : 'content_copy'
										}
										size={ 15 }
									/>
									<span>
										{ copied
											? __(
													'Copied!',
													'drivevault-for-woocommerce'
											  )
											: __(
													'Copy URI',
													'drivevault-for-woocommerce'
											  ) }
									</span>
								</ClipboardButton>
							</div>
							<span className="drivevault-help-text">
								{ __(
									'Must match the Authorized redirect URI in your Google Cloud Console credentials.',
									'drivevault-for-woocommerce'
								) }
							</span>
						</div>
					</div>
				</div>
			</div>
		</div>
	);
};

export default ApiSettings;

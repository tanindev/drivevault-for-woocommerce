import React from 'react';
import {
	RadioControl,
	RangeControl,
	ToggleControl,
	TextareaControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export const DownloadSettings = ( { settings, onChange } ) => {
	return (
		<div className="drivevault-settings-section">
			<div className="drivevault-ui-card">
				<div className="drivevault-ui-card__header">
					<h2>
						{ __(
							'Download & Delivery Options',
							'drivevault-for-woocommerce'
						) }
					</h2>
					<p className="drivevault-card-header-sub">
						{ __(
							'Configure how customer downloadable files are streamed and cached.',
							'drivevault-for-woocommerce'
						) }
					</p>
				</div>
				<div className="drivevault-ui-card__body">
					<div className="drivevault-form-vertical">
						<RadioControl
							label={ __(
								'File Delivery Method',
								'drivevault-for-woocommerce'
							) }
							help={ __(
								'Direct Streaming proxies the download securely through your server, concealing the direct Google Drive URL from the buyer and supporting HTTP range resuming.',
								'drivevault-for-woocommerce'
							) }
							selected={ settings?.download_method || 'stream' }
							options={ [
								{
									label: __(
										'Direct Chunked Stream (Recommended & Most Secure)',
										'drivevault-for-woocommerce'
									),
									value: 'stream',
								},
								{
									label: __(
										'Direct URL Redirect (Fast for public shared files)',
										'drivevault-for-woocommerce'
									),
									value: 'redirect',
								},
							] }
							onChange={ ( val ) =>
								onChange( 'download_method', val )
							}
						/>

						<hr className="drivevault-divider" />

						<TextareaControl
							label={ __(
								'Google Drive Disconnected / Inaccessible Error Message',
								'drivevault-for-woocommerce'
							) }
							value={
								settings?.error_message_disconnected ??
								__(
									'Error accessing Google Drive file: Google Drive account is not connected.',
									'drivevault-for-woocommerce'
								)
							}
							onChange={ ( val ) =>
								onChange( 'error_message_disconnected', val )
							}
							rows={ 2 }
							help={ __(
								'Message displayed to customers when attempting to download a Google Drive file while the account is disconnected or inaccessible.',
								'drivevault-for-woocommerce'
							) }
							placeholder={ __(
								'Error accessing Google Drive file: Google Drive account is not connected.',
								'drivevault-for-woocommerce'
							) }
						/>

						<hr className="drivevault-divider" />

						<RangeControl
							label={ __(
								'Stream Chunk Buffer Size (MB)',
								'drivevault-for-woocommerce'
							) }
							value={ settings?.chunk_size_mb || 8 }
							onChange={ ( val ) =>
								onChange( 'chunk_size_mb', val )
							}
							min={ 1 }
							max={ 32 }
							help={ __(
								'Memory buffer size in megabytes used when streaming large files from Google Drive.',
								'drivevault-for-woocommerce'
							) }
						/>

						<hr className="drivevault-divider" />

						<ToggleControl
							label={ __(
								'Enable Shared / Team Drives',
								'drivevault-for-woocommerce'
							) }
							checked={ !! settings?.enable_shared_drive }
							onChange={ ( val ) =>
								onChange( 'enable_shared_drive', val )
							}
							help={ __(
								'Allow browsing and linking downloadable files from Google Workspace Shared Drives (Team Drives).',
								'drivevault-for-woocommerce'
							) }
						/>

						<hr className="drivevault-divider" />

						<RangeControl
							label={ __(
								'Drive Directory Cache TTL (Minutes)',
								'drivevault-for-woocommerce'
							) }
							value={ Math.round(
								( settings?.cache_ttl || 600 ) / 60
							) }
							onChange={ ( val ) =>
								onChange( 'cache_ttl', val * 60 )
							}
							min={ 1 }
							max={ 120 }
							help={ __(
								'How long Google Drive file listings are cached in WordPress transients before refetching.',
								'drivevault-for-woocommerce'
							) }
						/>

						<hr className="drivevault-divider" />

						<ToggleControl
							label={ __(
								'Erase Plugin Data on Uninstall',
								'drivevault-for-woocommerce'
							) }
							checked={ !! settings?.delete_data_on_uninstall }
							onChange={ ( val ) =>
								onChange( 'delete_data_on_uninstall', val )
							}
							help={ __(
								'Check to delete stored Google tokens and settings if the plugin is deleted.',
								'drivevault-for-woocommerce'
							) }
						/>
					</div>
				</div>
			</div>
		</div>
	);
};

export default DownloadSettings;

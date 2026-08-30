import React from 'react';
import { __ } from '@wordpress/i18n';
import Icon from './Icon';

export const ConnectionBadge = ( { isConnected, accountEmail } ) => {
	if ( isConnected ) {
		return (
			<div className="drivevault-status-badge drivevault-status-badge--connected">
				<span className="drivevault-status-indicator drivevault-status-indicator--pulse"></span>
				<Icon
					name="check_circle"
					size={ 16 }
					className="drivevault-status-icon"
				/>
				<span className="drivevault-status-text">
					{ __( 'Connected', 'drivevault-for-woocommerce' ) }
					{ accountEmail && (
						<span className="drivevault-status-email">
							({ accountEmail })
						</span>
					) }
				</span>
			</div>
		);
	}

	return (
		<div className="drivevault-status-badge drivevault-status-badge--disconnected">
			<span className="drivevault-status-indicator"></span>
			<Icon
				name="error_outline"
				size={ 16 }
				className="drivevault-status-icon"
			/>
			<span className="drivevault-status-text">
				{ __( 'Not Connected', 'drivevault-for-woocommerce' ) }
			</span>
		</div>
	);
};

export default ConnectionBadge;

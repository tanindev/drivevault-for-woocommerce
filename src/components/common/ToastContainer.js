import React, { useEffect } from 'react';
import { useSelect, useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

const ToastIcon = ( { status } ) => {
	switch ( status ) {
		case 'success':
			return (
				<svg
					width="20"
					height="20"
					viewBox="0 0 20 20"
					fill="none"
					xmlns="http://www.w3.org/2000/svg"
					aria-hidden="true"
				>
					<path
						d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.707l-4.5 4.5a1 1 0 01-1.414 0l-2-2a1 1 0 111.414-1.414L8.5 10.586l3.793-3.793a1 1 0 111.414 1.414z"
						fill="currentColor"
					/>
				</svg>
			);
		case 'error':
			return (
				<svg
					width="20"
					height="20"
					viewBox="0 0 20 20"
					fill="none"
					xmlns="http://www.w3.org/2000/svg"
					aria-hidden="true"
				>
					<path
						d="M10 18a8 8 0 100-16 8 8 0 000 16zM9 9a1 1 0 012 0v3a1 1 0 11-2 0V9zm1-4a1.25 1.25 0 110 2.5A1.25 1.25 0 0110 5z"
						fill="currentColor"
					/>
				</svg>
			);
		case 'warning':
			return (
				<svg
					width="20"
					height="20"
					viewBox="0 0 20 20"
					fill="none"
					xmlns="http://www.w3.org/2000/svg"
					aria-hidden="true"
				>
					<path
						d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l6.518 11.593c.75 1.334-.213 2.988-1.742 2.988H3.48c-1.53 0-2.493-1.654-1.743-2.988L8.257 3.1zM11 13a1 1 0 10-2 0 1 1 0 002 0zm-1-6a1 1 0 00-1 1v3a1 1 0 102 0V8a1 1 0 00-1-1z"
						fill="currentColor"
					/>
				</svg>
			);
		case 'info':
		default:
			return (
				<svg
					width="20"
					height="20"
					viewBox="0 0 20 20"
					fill="none"
					xmlns="http://www.w3.org/2000/svg"
					aria-hidden="true"
				>
					<path
						d="M10 18a8 8 0 100-16 8 8 0 000 16zm-1-7a1 1 0 012 0v3a1 1 0 11-2 0v-3zm1-4a1.25 1.25 0 110 2.5A1.25 1.25 0 0110 7z"
						fill="currentColor"
					/>
				</svg>
			);
	}
};

const ToastItem = ( { toast, onDismiss } ) => {
	useEffect( () => {
		const duration = toast.duration !== undefined ? toast.duration : 4000;
		if ( duration > 0 ) {
			const timer = setTimeout( () => {
				onDismiss();
			}, duration );
			return () => clearTimeout( timer );
		}
	}, [ toast, onDismiss ] );

	const statusClass = toast.status
		? `drivevault-toast--${ toast.status }`
		: 'drivevault-toast--info';

	return (
		<div
			className={ `drivevault-toast ${ statusClass }` }
			role="alert"
			aria-live="polite"
		>
			<div className="drivevault-toast__icon">
				<ToastIcon status={ toast.status } />
			</div>
			<div className="drivevault-toast__content">
				<p>{ toast.message }</p>
			</div>
			<button
				type="button"
				className="drivevault-toast__close"
				onClick={ onDismiss }
				aria-label={ __(
					'Dismiss notification',
					'drivevault-for-woocommerce'
				) }
			>
				<svg
					width="14"
					height="14"
					viewBox="0 0 14 14"
					fill="none"
					xmlns="http://www.w3.org/2000/svg"
					aria-hidden="true"
				>
					<path
						d="M1.707.293A1 1 0 00.293 1.707L5.586 7 .293 12.293a1 1 0 101.414 1.414L7 8.414l5.293 5.293a1 1 0 001.414-1.414L8.414 7l5.293-5.293A1 1 0 0012.293.293L7 5.586 1.707.293z"
						fill="currentColor"
					/>
				</svg>
			</button>
		</div>
	);
};

export const ToastContainer = () => {
	const toasts = useSelect(
		( select ) => select( 'drivevault/drive' ).getToasts(),
		[]
	);
	const { removeToast } = useDispatch( 'drivevault/drive' );

	if ( ! toasts || toasts.length === 0 ) {
		return null;
	}

	return (
		<div
			className="drivevault-toast-container"
			aria-live="polite"
			role="region"
			aria-label={ __( 'Notifications', 'drivevault-for-woocommerce' ) }
		>
			{ toasts.map( ( toast ) => (
				<ToastItem
					key={ toast.id }
					toast={ toast }
					onDismiss={ () => removeToast( toast.id ) }
				/>
			) ) }
		</div>
	);
};

export default ToastContainer;

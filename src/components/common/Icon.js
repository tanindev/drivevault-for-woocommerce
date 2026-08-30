import React from 'react';

/**
 * Material / Custom Icon Component using font ligatures.
 */
export const Icon = ( { name, className = '', size, color, style = {} } ) => {
	const customStyles = {
		...style,
		...( size
			? { fontSize: typeof size === 'number' ? `${ size }px` : size }
			: {} ),
		...( color ? { color } : {} ),
	};

	return (
		<span
			className={ `drivevault-icon ${ className }` }
			style={ customStyles }
			aria-hidden="true"
		>
			{ name }
		</span>
	);
};

export default Icon;

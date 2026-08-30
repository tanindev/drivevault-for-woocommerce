const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );

module.exports = {
	...defaultConfig,
	entry: {
		index: path.resolve( process.cwd(), 'src', 'index.js' ),
		'product-picker': path.resolve(
			process.cwd(),
			'src',
			'product-picker.js'
		),
	},
	output: {
		...defaultConfig.output,
		path: path.resolve( process.cwd(), 'assets', 'build' ),
	},
};

const fs = require( 'fs' );
const path = require( 'path' );
const { execSync } = require( 'child_process' );

const pluginSlug = 'drivevault-for-woocommerce';
const zipFileName = `${ pluginSlug }.zip`;
const rootDir = path.resolve( __dirname, '..' );
const distDir = path.resolve( rootDir, 'dist' );
const pluginDistDir = path.resolve( distDir, pluginSlug );

console.log( '====================================================' );
console.log( '📦 DriveVault for WooCommerce - Production Release Build' );
console.log( '====================================================\n' );

// 1. Format Code
console.log( '🧹 [1/4] Formatting code with wp-scripts format...' );
try {
	execSync( 'npx wp-scripts format', { stdio: 'inherit', cwd: rootDir } );
	console.log( '  ✓ Code formatting complete.\n' );
} catch ( error ) {
	console.warn(
		'  ⚠️ Code formatting encountered warnings, continuing build...\n'
	);
}

// 2. Generate Languages / POT File
console.log( '🌐 [2/4] Generating translation language file (POT)...' );
try {
	execSync( 'node scripts/make-pot.js', { stdio: 'inherit', cwd: rootDir } );
	console.log( '  ✓ Language file generation complete.\n' );
} catch ( error ) {
	console.error( '  ❌ Error generating POT file:', error.message );
	process.exit( 1 );
}

// 3. Build Production Assets
console.log( '🚀 [3/4] Compiling production assets with wp-scripts...' );
try {
	execSync( 'npx wp-scripts build --webpack-copy-php', {
		stdio: 'inherit',
		cwd: rootDir,
	} );
	console.log( '  ✓ Production asset compilation complete.\n' );
} catch ( error ) {
	console.error( '  ❌ Error building assets:', error.message );
	process.exit( 1 );
}

// 4. Create Production Zip Package
console.log( `📦 [4/4] Packaging release archive into ${ zipFileName }...` );

// Clean dist directory & existing zip
if ( fs.existsSync( distDir ) ) {
	fs.rmSync( distDir, { recursive: true, force: true } );
}
if ( fs.existsSync( path.resolve( rootDir, zipFileName ) ) ) {
	fs.unlinkSync( path.resolve( rootDir, zipFileName ) );
}

fs.mkdirSync( pluginDistDir, { recursive: true } );

// Copy essential WordPress plugin files & folders
const includeItems = [
	'assets',
	'includes',
	'languages',
	'templates',
	'drivevault-for-woocommerce.php',
	'readme.txt',
	'uninstall.php',
];

const copyRecursive = ( src, dest ) => {
	const stat = fs.statSync( src );
	if ( stat.isDirectory() ) {
		fs.mkdirSync( dest, { recursive: true } );
		fs.readdirSync( src ).forEach( ( child ) => {
			// Exclude hidden files or source/dev directories
			if (
				child.startsWith( '.' ) ||
				child === 'node_modules' ||
				child === 'src'
			)
				return;
			copyRecursive( path.join( src, child ), path.join( dest, child ) );
		} );
	} else {
		fs.copyFileSync( src, dest );
	}
};

includeItems.forEach( ( item ) => {
	const srcPath = path.resolve( rootDir, item );
	if ( fs.existsSync( srcPath ) ) {
		copyRecursive( srcPath, path.resolve( pluginDistDir, item ) );
		console.log( `  ✓ Added ${ item }` );
	}
} );

execSync(
	`cd "${ distDir }" && zip -r -q "../${ zipFileName }" "${ pluginSlug }"`,
	{ stdio: 'inherit', cwd: rootDir }
);

// Clean up temp dist directory
fs.rmSync( distDir, { recursive: true, force: true } );

const zipStats = fs.statSync( path.resolve( rootDir, zipFileName ) );
const zipSizeKb = ( zipStats.size / 1024 ).toFixed( 2 );

console.log( '\n====================================================' );
console.log( `🎉 Success! Production release is ready:` );
console.log( `   File: ${ zipFileName } (${ zipSizeKb } KB)` );
console.log( `   Path: ${ path.resolve( rootDir, zipFileName ) }` );
console.log( '====================================================\n' );

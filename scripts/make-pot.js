const fs = require( 'fs' );
const path = require( 'path' );
const { execSync } = require( 'child_process' );

const pluginSlug = 'drivevault-for-woocommerce';
const rootDir = path.resolve( __dirname, '..' );
const languagesDir = path.resolve( rootDir, 'languages' );
const potFile = path.resolve( languagesDir, `${ pluginSlug }.pot` );

if ( ! fs.existsSync( languagesDir ) ) {
	fs.mkdirSync( languagesDir, { recursive: true } );
}

console.log(
	'🌐 Generating translation POT file for DriveVault for WooCommerce...'
);

let generatedWithWpCli = false;

// 1. Try WP-CLI first if available
try {
	execSync(
		`wp i18n make-pot . "${ potFile }" --slug="${ pluginSlug }" --exclude="node_modules,dist,assets/build,scripts"`,
		{
			cwd: rootDir,
			stdio: 'inherit',
		}
	);
	generatedWithWpCli = true;
} catch ( e ) {
	// WP-CLI not available or errored, fallback to pure Node.js extractor
	generatedWithWpCli = false;
}

if ( ! generatedWithWpCli ) {
	console.log(
		'ℹ️ WP-CLI not detected in current PATH, using Node.js extractor fallback...'
	);

	const entries = new Map();

	const scanFile = ( filePath, relPath ) => {
		const content = fs.readFileSync( filePath, 'utf8' );
		const lines = content.split( '\n' );

		// Regex for standard WordPress translation functions: __, _e, esc_html__, esc_html_e, esc_attr__, esc_attr_e, _x, _ex
		const regex =
			/\b(?:__|_e|esc_html__|esc_html_e|esc_attr__|esc_attr_e)\s*\(\s*(['"])((?:(?!\1)[^\\]|\\.)*)\1\s*,\s*(['"])(?:drivevault-for-woocommerce|easy-woocommerce-downloadable-products-for-google-drive)\3\s*\)/g;

		lines.forEach( ( line, index ) => {
			let match;
			while ( ( match = regex.exec( line ) ) !== null ) {
				const rawStr = match[ 2 ]
					.replace( /\\'/g, "'" )
					.replace( /\\"/g, '"' );
				if ( ! rawStr ) return;

				if ( ! entries.has( rawStr ) ) {
					entries.set( rawStr, [] );
				}
				entries.get( rawStr ).push( `${ relPath }:${ index + 1 }` );
			}
		} );
	};

	const traverse = ( dir ) => {
		const files = fs.readdirSync( dir );
		for ( const file of files ) {
			if (
				file.startsWith( '.' ) ||
				file === 'node_modules' ||
				file === 'dist' ||
				file === 'assets' ||
				file === 'scripts'
			) {
				continue;
			}
			const fullPath = path.join( dir, file );
			const relPath = path.relative( rootDir, fullPath );
			const stat = fs.statSync( fullPath );
			if ( stat.isDirectory() ) {
				traverse( fullPath );
			} else if ( file.endsWith( '.php' ) || file.endsWith( '.js' ) ) {
				scanFile( fullPath, relPath );
			}
		}
	};

	traverse( rootDir );

	let potContent = `# Copyright (C) 2026 Tanin Ahmed
# This file is distributed under the GPL-2.0-or-later.
msgid ""
msgstr ""
"Project-Id-Version: DriveVault for WooCommerce 1.0.0\\n"
"Report-Msgid-Bugs-To: https://wordpress.org/support/plugin/drivevault-for-woocommerce\\n"
"Last-Translator: FULL NAME <EMAIL@ADDRESS>\\n"
"Language-Team: LANGUAGE <LL@li.org>\\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=UTF-8\\n"
"Content-Transfer-Encoding: 8bit\\n"
"POT-Creation-Date: ${ new Date().toISOString() }\\n"
"PO-Revision-Date: YEAR-MO-DA HO:MI+ZONE\\n"
"X-Generator: DriveVault i18n Generator\\n"
"X-Domain: ${ pluginSlug }\\n"

`;

	entries.forEach( ( locations, msgid ) => {
		locations.forEach( ( loc ) => {
			potContent += `#: ${ loc }\n`;
		} );
		potContent += `msgid "${ msgid.replace( /"/g, '\\"' ) }"\n`;
		potContent += `msgstr ""\n\n`;
	} );

	fs.writeFileSync( potFile, potContent, 'utf8' );
}

console.log( `\n🎉 Success! POT file generated at:\n   ${ potFile }\n` );

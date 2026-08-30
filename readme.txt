=== DriveVault for WooCommerce ===
Contributors: tanindev
Tags: woocommerce, google drive, downloadable products, digital downloads, cloud storage
Requires at least: 5.8
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Connect Google Drive to WooCommerce Downloadable Products with a modern React UI and secure streaming downloads.

== Description ==

**DriveVault for WooCommerce** allows store owners to effortlessly attach files stored on Google Drive directly to WooCommerce downloadable products.

Built with a fast, modern React user interface powered by WordPress core packages (`@wordpress/components`, `@wordpress/data`, `@wordpress/api-fetch`), you can browse folders, search files, and insert download links directly inside the WooCommerce product editor.

### Key Features:

* **Seamless Google Drive Connection**: Connect your Google Cloud project using OAuth 2.0 with a single authorization flow.
* **Modern React File Picker**: Browse Google Drive folders, search in real-time, and multi-select files directly from the WooCommerce Product Edit screen.
* **Simple & Variable Products Support**: Full compatibility with both single downloadable products and variable product variations.
* **High-Speed Secure Streaming**: Files are proxied directly and securely through your server, concealing the underlying Google Drive URLs and enforcing WooCommerce order access rules.
* **HTTP Range & Resume Support**: Large files support chunked streaming with resumable download capability.
* **Shared Drives / Team Drives Support**: Browse and attach files from Google Workspace Shared Drives.
* **High-Performance Order Storage (HPOS) Ready**: Fully compatible with WooCommerce HPOS and Custom Order Tables.
* **Fast Caching**: Folder listings and metadata are cached using WordPress Transients with instant manual refresh.

== Installation ==

1. Upload the `drivevault-for-woocommerce` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **WooCommerce > Google Drive** in your WordPress admin menu.
4. Enter your Google Cloud OAuth Client ID and Client Secret, then click **Authorize & Connect Google Drive**.
5. When editing any Downloadable Product, click **Add from Google Drive** to select and attach files.

== Frequently Asked Questions ==

= Does this plugin expose my Google Drive file links to buyers? =
No. When using the default **Direct Chunked Stream** mode, download requests are verified by WooCommerce and securely piped directly to the buyer without ever exposing the Google Drive URL.

= Are large files supported? =
Yes. The plugin uses chunked streaming and respects HTTP range requests so large downloads won't exhaust server memory or fail on unstable connections.

= Does it support Google Shared Drives (Team Drives)? =
Yes! You can enable Shared Drive support in the settings to access all files across your organization.

== Changelog ==

= 1.0.0 =
* Initial release with Google Drive OAuth2 integration, React File Picker modal, and secure WooCommerce download streamer.

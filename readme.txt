=== DriveVault for WooCommerce ===
Contributors: tanindev
Tags: woocommerce, google drive, downloadable products, digital downloads, cloud storage
Requires at least: 5.8
Tested up to: 7.1
Requires PHP: 7.4
Requires Plugins: woocommerce
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

== External Services ==

This plugin connects to external 3rd-party services provided by Google to enable Google Drive integration for WooCommerce downloadable products.

= Google OAuth 2.0 API =
* **What the service is and what it is used for**: Securely authenticates the store administrator's Google account via OAuth 2.0 and generates API access and refresh tokens.
* **What data is sent and when**:
    * When connecting your Google account in plugin settings, your OAuth Client ID, Client Secret, authorization code, and redirect URI are transmitted to Google's token endpoint (`https://oauth2.googleapis.com/token`) to exchange for API access/refresh tokens.
    * When viewing connection status, basic user profile information (email address and display name) is retrieved from `https://www.googleapis.com/oauth2/v2/userinfo` to display the connected account in the admin dashboard.
    * When disconnecting your account, a revocation request is sent to `https://oauth2.googleapis.com/revoke` to invalidate tokens.
* **Service Provider**: Google LLC
* **Terms of Service**: [Google Terms of Service](https://policies.google.com/terms) (https://policies.google.com/terms)
* **Privacy Policy**: [Google Privacy Policy](https://policies.google.com/privacy) (https://policies.google.com/privacy)

= Google Drive API v3 =
* **What the service is and what it is used for**: Browses Google Drive folders, searches files, retrieves file metadata, and securely streams downloadable product files attached to WooCommerce products.
* **What data is sent and when**:
    * When store managers browse or search files within the WooCommerce product editor, API queries containing folder IDs, search terms, and pagination tokens are sent to `https://www.googleapis.com/drive/v3/files`.
    * When customers download purchased digital files, file requests are sent using the store's OAuth token to stream the file binary content directly from `https://www.googleapis.com/drive/v3/files/{file_id}?alt=media`.
* **Service Provider**: Google LLC
* **Terms of Service**: [Google APIs Terms of Service](https://developers.google.com/terms) (https://developers.google.com/terms)
* **Privacy Policy**: [Google Privacy Policy](https://policies.google.com/privacy) (https://policies.google.com/privacy)

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

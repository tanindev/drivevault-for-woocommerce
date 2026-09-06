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

Connect Google Drive to WooCommerce Downloadable Products with a modern React UI and native Google Drive downloads.

== Description ==

**DriveVault for WooCommerce** allows store owners to effortlessly attach files stored on Google Drive directly to WooCommerce downloadable products.

Built with a fast, modern React user interface powered by WordPress core packages (`@wordpress/components`, `@wordpress/data`, `@wordpress/api-fetch`), you can browse folders, search files, and insert download links directly inside the WooCommerce product editor.

### Key Features:

* **Seamless Google Drive Connection**: Connect your Google Cloud project using OAuth 2.0 with a single authorization flow.
* **Modern React File Picker**: Browse Google Drive folders, search in real-time, and multi-select files directly from the WooCommerce Product Edit screen.
* **Simple & Variable Products Support**: Full compatibility with both single downloadable products and variable product variations.
* **Native Google Drive Downloads**: Directs authorized buyers to Google Drive's native browser download endpoint while verifying WooCommerce order access rules.
* **Pro Add-on Extensibility**: Seamlessly integrates with the DriveVault Pro add-on for advanced server-side chunked proxy streaming, Google Workspace Shared / Team Drives support, URL masking, and HTTP Range resumes.
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
* **What the service is and what it is used for**: Browses Google Drive folders, searches files, retrieves file metadata, and serves downloadable product files attached to WooCommerce products.
* **What data is sent and when**:
    * When store managers browse or search files within the WooCommerce product editor, API queries containing folder IDs, search terms, and pagination tokens are sent to `https://www.googleapis.com/drive/v3/files`.
    * When customers download purchased digital files, authorized buyers are redirected to Google Drive's native browser download endpoint, or securely proxied when using the Pro add-on.
* **Service Provider**: Google LLC
* **Terms of Service**: [Google APIs Terms of Service](https://developers.google.com/terms) (https://developers.google.com/terms)
* **Privacy Policy**: [Google Privacy Policy](https://policies.google.com/privacy) (https://policies.google.com/privacy)

== Third-Party Assets ==

* **Google Material Symbols / Icons Font**
    * File: `assets/fonts/drivevault-icon.woff2`
    * Source: https://fonts.google.com/icons
    * Copyright: Google LLC
    * License: Apache License 2.0 (https://www.apache.org/licenses/LICENSE-2.0)

== Source Code and Build ==

The unminified React source code for the admin dashboard and product file picker is open source and publicly hosted on GitHub:
* GitHub Repository: https://github.com/tanindev/drivevault-for-woocommerce

To compile production assets:
1. Ensure Node.js (v18+) and npm are installed.
2. Run `npm install`
3. Run `npm run build` to generate production assets in `assets/build/`.

== Installation ==

1. Upload the `drivevault-for-woocommerce` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **WooCommerce > Google Drive** in your WordPress admin menu.
4. Enter your Google Cloud OAuth Client ID and Client Secret, then click **Authorize & Connect Google Drive**.
5. When editing any Downloadable Product, click **Add from Google Drive** to select and attach files.

== Frequently Asked Questions ==

= How are downloads delivered to customers? =
WooCommerce verifies the customer's purchase permissions, then securely directs the customer's browser to the Google Drive file download endpoint.

= Can I stream files through my server to hide Google Drive URLs? =
Yes! Server-side chunked proxy streaming (which conceals Google Drive URLs and supports HTTP Range resumable transfers) is supported via the DriveVault Pro add-on.

= Does it support Google Shared Drives (Team Drives)? =
Support for Google Workspace Shared Drives (Team Drives) is available with the DriveVault Pro add-on, allowing store managers to browse and link files shared across their organization.

== Changelog ==

= 1.0.0 =
* Initial release with Google Drive OAuth2 integration, React File Picker modal, and WooCommerce Google Drive download integration.

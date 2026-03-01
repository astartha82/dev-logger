=== Dev Logger ===
Contributors: astartha82
Tags: logger, debug, logging, developer, log
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A lightweight developer-friendly logger for WordPress. One function call logs any variable with its name, file, and line number automatically.

== Description ==

Dev Logger gives developers a fast and flexible way to log any data to `.log` files stored inside `wp-content/uploads/`.

No configuration required — just call `devlog( $myVar )` anywhere in your theme or plugin code.

**Features:**

* One required argument — just pass the variable, the rest is captured automatically
* Variable name, file, and line number are detected from the source code
* Five log levels: debug, info, warning, error, critical
* Shorthand helpers: `devlog_error()`, `devlog_warning()`, and more
* Log to a default daily file or route to a custom file per call
* Log viewer in the WordPress admin — browse, read, and download log files
* Color-coded log levels in the viewer (dark theme)
* Manual "Delete All Logs" button
* Auto-cleanup: delete log files older than N days via WP Cron
* Log directory is protected from direct browser access (.htaccess + index.php)
* Configurable directory name and file prefix from the Settings page

**Basic usage:**

Just pass any variable — the name, file, and line are captured automatically:
`devlog( $myVar );`

Output in the log file:
`[14:32:10] [DEBUG] my-plugin.php:42 | $myVar: some value`

Log with a specific level:
`devlog( $order_id, 'error' );`

Or use a shorthand helper:
`devlog_debug( $response );`
`devlog_info( $user_id );`
`devlog_warning( $query );`
`devlog_error( $order_id );`
`devlog_critical( $exception );`

Log a plain string message:
`devlog( 'Payment gateway unreachable' );`

Log to a separate file (3rd argument):
`devlog( $order_id, 'error', 'payments' );`
`devlog_error( $order_id, 'payments' );`

== Installation ==

1. Upload the `dev-logger` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Go to **Settings → Dev Logger** to configure the directory, file prefix, and auto-cleanup retention.
4. Use `devlog()` anywhere in your theme or plugin code.
5. View logs at **Settings → Dev Logger → View Logs**.

== Frequently Asked Questions ==

= Where are the log files stored? =

By default in `wp-content/uploads/devlog-logs/`. You can change the directory name in **Settings → Dev Logger**.

= Can I log to separate files? =

Yes. Pass the filename as the third argument to `devlog()` or the second argument to a helper:
`devlog( $var, 'debug', 'my-file' );`
`devlog_error( $var, 'my-file' );`
This creates a file like `devlog_my-file_2024-02-06.log`.

= Can I view log files in the admin? =

Yes. Go to **Settings → Dev Logger → View Logs**. You can browse all log files, view their color-coded content, download them, or delete individual files.

= How does auto-cleanup work? =

Set a retention period in **Settings → Dev Logger** (e.g. 30 days). A WP Cron job runs daily and deletes any log files older than that. Set to 0 to disable auto-cleanup.

= Are log files accessible from the browser? =

No. When the log directory is created, the plugin automatically adds an `.htaccess` file that blocks direct access to `.log` files on Apache servers.

= Is this plugin safe to use on production? =

The plugin is intended for development and debugging. It is recommended to remove or deactivate the plugin on production sites when debugging is done.

== Screenshots ==

1. Settings page — configure log directory, file prefix, and auto-cleanup retention.
2. Log viewer — color-coded log entries with DEBUG, INFO, WARNING, ERROR, and CRITICAL levels.

== Changelog ==

= 2.0.0 =
* New: variable name, file, and line number are captured automatically on every log call
* New: log levels — debug, info, warning, error, critical
* New: shorthand helpers — devlog_debug(), devlog_info(), devlog_warning(), devlog_error(), devlog_critical()
* New: log viewer in WordPress admin with color-coded output
* New: download and delete individual log files from the viewer
* New: "Delete All Logs" button
* New: auto-cleanup via WP Cron with configurable retention period
* New: log directory is automatically protected with .htaccess and index.php
* Changed: main function renamed from _log() to devlog()
* Security: added output escaping and input sanitization throughout
* Security: added ABSPATH check
* Improved: plugin header now includes all required fields for wordpress.org
* Added: uninstall.php to clean up options and cron jobs on plugin deletion

= 1.0.0 =
* Initial release

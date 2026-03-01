<?php
/**
 * Runs on plugin uninstall.
 * Removes plugin options from the database.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'devlog_options' );
wp_clear_scheduled_hook( 'devlog_auto_cleanup' );

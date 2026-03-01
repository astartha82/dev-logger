<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DevLog_Cleanup {

	const CRON_HOOK = 'devlog_auto_cleanup';

	public static function init() {
		add_action( self::CRON_HOOK, array( __CLASS__, 'run_auto_cleanup' ) );
		add_action( 'admin_init', array( __CLASS__, 'handle_delete_all' ) );
	}

	/**
	 * Called on plugin activation. Schedules the daily cron job.
	 */
	public static function activate() {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'daily', self::CRON_HOOK );
		}
		// Protect the log directory if it already exists from a previous installation.
		$dir = self::get_log_dir();
		if ( file_exists( $dir ) && function_exists( 'devlog_protect_dir' ) ) {
			devlog_protect_dir( $dir );
		}
	}

	/**
	 * Called on plugin deactivation. Removes the cron job.
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( self::CRON_HOOK );
	}

	/**
	 * Cron callback. Deletes log files older than the configured retention period.
	 */
	public static function run_auto_cleanup() {
		$options = get_option( 'devlog_options' );
		$days    = isset( $options['devlog_retention'] ) ? (int) $options['devlog_retention'] : 30;
		if ( $days <= 0 ) {
			return; // 0 = auto-cleanup disabled.
		}
		self::delete_logs_older_than( $days );
	}

	/**
	 * Handles the "Delete All Logs" button form submission.
	 */
	public static function handle_delete_all() {
		if ( ! isset( $_POST['devlog_action'] ) || $_POST['devlog_action'] !== 'delete_all_logs' ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		check_admin_referer( 'devlog_delete_all_logs' );

		self::delete_all_logs();

		wp_safe_redirect( admin_url( 'options-general.php?page=devlog_viewer&devlog_msg=all_deleted' ) );
		exit;
	}

	/**
	 * Deletes all .log files in the log directory.
	 */
	public static function delete_all_logs() {
		self::delete_logs_older_than( 0 );
	}

	/**
	 * Returns the absolute path to the log directory.
	 */
	private static function get_log_dir() {
		$options   = get_option( 'devlog_options' );
		$devlogdir = ! empty( $options['devlog_dir'] ) ? $options['devlog_dir'] : 'devlog-logs';
		return wp_get_upload_dir()['basedir'] . '/' . $devlogdir . '/';
	}

	/**
	 * Deletes log files older than $days days.
	 * If $days is 0, all log files are deleted.
	 */
	private static function delete_logs_older_than( $days ) {
		$dir = self::get_log_dir();
		if ( ! file_exists( $dir ) || ! is_dir( $dir ) ) {
			return;
		}

		$files = glob( $dir . '*.log' );
		if ( ! $files ) {
			return;
		}

		// PHP_INT_MAX as cutoff means every file's mtime will be older → delete all.
		$cutoff = $days > 0 ? time() - ( (int) $days * DAY_IN_SECONDS ) : PHP_INT_MAX;

		foreach ( $files as $file ) {
			if ( filemtime( $file ) < $cutoff ) {
				wp_delete_file( $file );
			}
		}
	}
}

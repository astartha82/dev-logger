<?php
/**
 * Plugin Name: Dev Logger
 * Plugin URI:  https://github.com/astartha82/dev-logger
 * Description: A lightweight developer-friendly logger for WordPress. Log variables, objects, arrays and messages to .log files.
 * Author:      Lilith Zakharyan
 * Author URI:  https://github.com/astartha82
 * Version:     2.0.0
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * Text Domain: dev-logger
 * Domain Path: /languages
 * License:     GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Internal: create .htaccess and index.php in the log directory to block direct browser access.
 */
function devlog_protect_dir( $dir ) {
	$htaccess = $dir . '.htaccess';
	if ( ! file_exists( $htaccess ) ) {
		$rules  = "# Block direct access to log files\n";
		$rules .= "<Files ~ \"\\.log$\">\n";
		$rules .= "    Order allow,deny\n";
		$rules .= "    Deny from all\n";
		$rules .= "</Files>\n";
		$rules .= "<IfModule mod_authz_core.c>\n";
		$rules .= "    <Files ~ \"\\.log$\">\n";
		$rules .= "        Require all denied\n";
		$rules .= "    </Files>\n";
		$rules .= "</IfModule>\n";
		file_put_contents( $htaccess, $rules ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_write_file_put_contents
	}
	$index = $dir . 'index.php';
	if ( ! file_exists( $index ) ) {
		file_put_contents( $index, "<?php\n// Silence is golden.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_write_file_put_contents
	}
}

/**
 * Internal: write a log entry. Not intended for direct use.
 *
 * @param mixed        $log     Value to log.
 * @param string       $level   Log level.
 * @param string|false $logfile Optional filename suffix.
 * @param int          $depth   debug_backtrace depth to find the real caller.
 */
function devlog_write( $log, $level, $logfile, $depth ) {
	$allowed_levels = array( 'debug', 'info', 'warning', 'error', 'critical' );
	$level = strtolower( (string) $level );
	if ( ! in_array( $level, $allowed_levels, true ) ) {
		$level = 'debug';
	}

	// Find the real caller (the line where devlog / devlog_* was called).
	$backtrace   = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, $depth + 1 ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace
	$caller      = isset( $backtrace[ $depth ] ) ? $backtrace[ $depth ] : array();
	$caller_file = isset( $caller['file'] ) ? basename( $caller['file'] ) : 'unknown';
	$caller_line = isset( $caller['line'] ) ? (int) $caller['line'] : 0;

	// Try to read the variable name from the source line.
	$label = '';
	if ( isset( $caller['file'] ) && is_readable( $caller['file'] ) ) {
		$source_lines = file( $caller['file'] );
		$source_line  = isset( $source_lines[ $caller_line - 1 ] ) ? trim( $source_lines[ $caller_line - 1 ] ) : '';
		// Match the first argument if it looks like a variable: $var, $obj->prop, $arr['key'].
		if ( preg_match( '/devlog\w*\s*\(\s*(\$[\w\->\[\'"\]]+)/', $source_line, $matches ) ) {
			$label = trim( $matches[1] );
		}
	}

	// Format the value.
	if ( is_bool( $log ) ) {
		$value = $log ? 'yes' : 'no';
	} elseif ( is_array( $log ) || is_object( $log ) ) {
		$value = print_r( $log, true ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
	} else {
		$value = (string) $log;
	}

	// Build the log line.
	$timestamp = '[' . current_time( 'H:i:s' ) . ']';
	$tag       = '[' . strtoupper( $level ) . ']';
	$location  = $caller_file . ':' . $caller_line;

	if ( $label !== '' ) {
		$entry = $label . ': ' . $value;
	} else {
		$entry = $value !== '' ? $value : '(empty)';
	}

	$separator = PHP_EOL . '**************************' . PHP_EOL;
	$line      = $timestamp . ' ' . $tag . ' ' . $location . ' | ' . $entry . PHP_EOL;

	// Resolve log file path.
	$options    = get_option( 'devlog_options' );
	$devlogdir  = ! empty( $options['devlog_dir'] )  ? $options['devlog_dir']  : 'devlog-logs';
	$devlogfile = ! empty( $options['devlog_file'] ) ? $options['devlog_file'] : 'devlog';

	$dir = wp_get_upload_dir()['basedir'] . '/' . $devlogdir . '/';
	if ( ! file_exists( $dir ) ) {
		wp_mkdir_p( $dir );
		devlog_protect_dir( $dir );
	}

	if ( $logfile ) {
		$filepath = $dir . $devlogfile . '_' . $logfile . '_' . current_time( 'Y-m-d' ) . '.log';
	} else {
		$filepath = $dir . $devlogfile . '_' . current_time( 'Y-m-d' ) . '.log';
	}

	file_put_contents( $filepath, $separator . $line, FILE_APPEND | LOCK_EX ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_write_file_put_contents
}

if ( ! function_exists( 'devlog' ) ) {
	/**
	 * Log any value to a .log file.
	 * Automatically captures the variable name, file, and line number of the call.
	 *
	 * @param mixed        $log     Value to log: variable, string, array, object, bool.
	 * @param string       $level   Log level: debug, info, warning, error, critical.
	 * @param string|false $logfile Optional filename suffix for a separate log file.
	 */
	function devlog( $log, $level = 'debug', $logfile = false ) {
		devlog_write( $log, $level, $logfile, 1 );
	}
}

if ( ! function_exists( 'devlog_debug' ) ) {
	function devlog_debug( $log, $logfile = false ) {
		devlog_write( $log, 'debug', $logfile, 2 );
	}
}

if ( ! function_exists( 'devlog_info' ) ) {
	function devlog_info( $log, $logfile = false ) {
		devlog_write( $log, 'info', $logfile, 2 );
	}
}

if ( ! function_exists( 'devlog_warning' ) ) {
	function devlog_warning( $log, $logfile = false ) {
		devlog_write( $log, 'warning', $logfile, 2 );
	}
}

if ( ! function_exists( 'devlog_error' ) ) {
	function devlog_error( $log, $logfile = false ) {
		devlog_write( $log, 'error', $logfile, 2 );
	}
}

if ( ! function_exists( 'devlog_critical' ) ) {
	function devlog_critical( $log, $logfile = false ) {
		devlog_write( $log, 'critical', $logfile, 2 );
	}
}

require_once plugin_dir_path( __FILE__ ) . 'includes/class-devlog-viewer.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-devlog-cleanup.php';

register_activation_hook( __FILE__, array( 'DevLog_Cleanup', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'DevLog_Cleanup', 'deactivate' ) );

DevLog_Viewer::init();
DevLog_Cleanup::init();

add_action( 'admin_init', 'devlog_options_init' );
add_action( 'admin_menu', 'devlog_options_page' );

function devlog_options_init() {
	register_setting(
		'devlog_options_group',
		'devlog_options',
		'devlog_options_validate'
	);
}

function devlog_options_page() {
	add_options_page(
		'Dev Logger Options',
		'Dev Logger Options',
		'manage_options',
		'devlog_options',
		'devlog_render_options'
	);
}

function devlog_render_options() {
?>
		<div class="wrap">
				<form method="post" action="options.php">
<?php
	settings_fields( 'devlog_options_group' );
	$options   = get_option( 'devlog_options' );
	$dir       = ! empty( $options['devlog_dir'] )       ? $options['devlog_dir']       : 'devlog-logs';
	$file      = ! empty( $options['devlog_file'] )      ? $options['devlog_file']      : 'devlog';
	$retention = isset( $options['devlog_retention'] ) ? (int) $options['devlog_retention'] : 30;
?>
						<h1><?php esc_html_e( 'Dev Logger — Settings', 'dev-logger' ); ?></h1>
						<table class="form-table">
								<tr valign="top">
										<th scope="row"><?php esc_html_e( 'Log directory', 'dev-logger' ); ?></th>
										<td>
											<input type="text" id="devlog_dir" name="devlog_options[devlog_dir]" value="<?php echo esc_attr( $dir ); ?>" style="width:260px;">
											<p class="description"><?php esc_html_e( 'Folder name inside wp-content/uploads/', 'dev-logger' ); ?></p>
										</td>
								</tr>
								<tr valign="top">
										<th scope="row"><?php esc_html_e( 'File prefix', 'dev-logger' ); ?></th>
										<td>
											<input type="text" id="devlog_file" name="devlog_options[devlog_file]" value="<?php echo esc_attr( $file ); ?>" style="width:260px;">
											<p class="description"><?php esc_html_e( 'Log files will be named like: prefix_2024-01-31.log', 'dev-logger' ); ?></p>
										</td>
								</tr>
								<tr valign="top">
										<th scope="row"><?php esc_html_e( 'Auto-delete logs older than', 'dev-logger' ); ?></th>
										<td>
											<input type="number" id="devlog_retention" name="devlog_options[devlog_retention]" value="<?php echo esc_attr( $retention ); ?>" min="0" style="width:80px;"> <?php esc_html_e( 'days', 'dev-logger' ); ?>
											<p class="description"><?php esc_html_e( 'Set to 0 to disable automatic cleanup. Runs once daily via WP Cron.', 'dev-logger' ); ?></p>
										</td>
								</tr>
						</table>
						<p class="submit">
								<input type="submit" class="button-primary" value="<?php esc_attr_e( 'Save Changes', 'dev-logger' ); ?>" />
						</p>
				</form>
		</div>
<style>
		code {display: block; margin: 4px 0;}
</style>

<h2><?php esc_html_e( 'Usage', 'dev-logger' ); ?></h2>

<p class="description">
<?php esc_html_e( 'Just pass any variable — the name, file, and line are captured automatically:', 'dev-logger' ); ?>
<code>devlog( $myVar );</code>
<?php esc_html_e( 'Output in log file:', 'dev-logger' ); ?>
<code>[14:32:10] [DEBUG] my-plugin.php:42 | $myVar: some value</code>
</p>

<p class="description">
<?php esc_html_e( 'Log with a specific level:', 'dev-logger' ); ?>
<code>devlog( $order_id, 'error' );</code>
<?php esc_html_e( 'Or use a shorthand helper:', 'dev-logger' ); ?>
<code>devlog_debug( $response );
devlog_info( $user_id );
devlog_warning( $query );
devlog_error( $order_id );
devlog_critical( $exception );</code>
</p>

<p class="description">
<?php esc_html_e( 'Log a plain string message:', 'dev-logger' ); ?>
<code>devlog( 'Payment gateway unreachable' );</code>
</p>

<p class="description">
<?php esc_html_e( 'Log to a separate file (3rd argument):', 'dev-logger' ); ?>
<code>devlog( $order_id, 'error', 'payments' );
devlog_error( $order_id, 'payments' );</code>
<?php esc_html_e( 'This creates a file like:', 'dev-logger' ); ?>
<code>wp-content/uploads/<?php echo esc_html( $options['devlog_dir'] ); ?>/<?php echo esc_html( $options['devlog_file'] ); ?>_payments_<?php echo esc_html( current_time( 'Y-m-d' ) ); ?>.log</code>
</p>
<?php
}

function devlog_options_validate( $input ) {
	$clean = array();
	$clean['devlog_dir']       = isset( $input['devlog_dir'] )       ? sanitize_file_name( $input['devlog_dir'] )       : 'devlog-logs';
	$clean['devlog_file']      = isset( $input['devlog_file'] )      ? sanitize_file_name( $input['devlog_file'] )      : 'devlog';
	$clean['devlog_retention'] = isset( $input['devlog_retention'] ) ? absint( $input['devlog_retention'] )             : 30;
	return $clean;
}

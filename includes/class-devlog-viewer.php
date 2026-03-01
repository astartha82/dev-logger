<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DevLog_Viewer {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_page' ) );
		add_action( 'admin_init', array( __CLASS__, 'handle_actions' ) );
	}

	public static function add_page() {
		add_options_page(
			__( 'Astartha Dev Logger — View Logs', 'astartha-dev-logger' ),
			__( 'View Logs', 'astartha-dev-logger' ),
			'manage_options',
			'devlog_viewer',
			array( __CLASS__, 'render' )
		);
	}

	/**
	 * Handle file download and delete actions before any output.
	 */
	public static function handle_actions() {
		if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'devlog_viewer' ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Action value is checked against a fixed allowlist; nonce is verified per specific action below.
		$action = isset( $_GET['devlog_action'] ) ? sanitize_key( wp_unslash( $_GET['devlog_action'] ) ) : '';

		if ( $action === 'delete' && isset( $_GET['_wpnonce'], $_GET['file'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'devlog_delete_file' ) ) {
				wp_die( esc_html__( 'Security check failed.', 'astartha-dev-logger' ) );
			}
			$file = self::resolve_file( sanitize_file_name( wp_unslash( $_GET['file'] ) ) );
			if ( $file && file_exists( $file ) ) {
				wp_delete_file( $file );
			}
			wp_safe_redirect( admin_url( 'options-general.php?page=devlog_viewer&devlog_msg=deleted' ) );
			exit;
		}

		if ( $action === 'download' && isset( $_GET['_wpnonce'], $_GET['file'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'devlog_download_file' ) ) {
				wp_die( esc_html__( 'Security check failed.', 'astartha-dev-logger' ) );
			}
			$file = self::resolve_file( sanitize_file_name( wp_unslash( $_GET['file'] ) ) );
			if ( $file && file_exists( $file ) ) {
				$filename = basename( $file );
				header( 'Content-Type: text/plain; charset=utf-8' );
				header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
				header( 'Content-Length: ' . filesize( $file ) );
				readfile( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
				exit;
			}
		}
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
	 * Safely resolves a log filename to an absolute path.
	 * Returns false if the file is outside the log directory.
	 */
	private static function resolve_file( $filename ) {
		if ( empty( $filename ) ) {
			return false;
		}
		$dir  = self::get_log_dir();
		$path = realpath( $dir . $filename );
		$base = realpath( $dir );
		if ( ! $path || ! $base || strpos( $path, $base . DIRECTORY_SEPARATOR ) !== 0 ) {
			return false;
		}
		return $path;
	}

	/**
	 * Render the Log Viewer admin page.
	 */
	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'astartha-dev-logger' ) );
		}

		$dir   = self::get_log_dir();
		$files = ( file_exists( $dir ) && is_dir( $dir ) ) ? glob( $dir . '*.log' ) : array();
		if ( $files ) {
			usort( $files, function( $a, $b ) {
				return filemtime( $b ) - filemtime( $a );
			} );
		} else {
			$files = array();
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only redirect indicator set by nonce-verified actions.
		$devlog_msg = isset( $_GET['devlog_msg'] ) ? sanitize_key( wp_unslash( $_GET['devlog_msg'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		// Determine which file to show.
		$current_filename = '';
		$current_content  = '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only file display; sanitized and protected by resolve_file() path traversal check.
		if ( ! empty( $_GET['file'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$requested = sanitize_file_name( wp_unslash( $_GET['file'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$resolved  = self::resolve_file( $requested );
			if ( $resolved ) {
				$current_filename = basename( $resolved );
				$current_content  = file_get_contents( $resolved ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			}
		} elseif ( ! empty( $files ) ) {
			$current_filename = basename( $files[0] );
			$current_content  = file_get_contents( $files[0] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		}

		$base_url = admin_url( 'options-general.php?page=devlog_viewer' );
		?>
		<div class="wrap">
			<h1>
				<?php esc_html_e( 'Astartha Dev Logger — Logs', 'astartha-dev-logger' ); ?>
				<form method="post" style="display:inline; margin-left:12px;">
					<?php wp_nonce_field( 'devlog_delete_all_logs' ); ?>
					<input type="hidden" name="devlog_action" value="delete_all_logs">
					<button type="submit" class="page-title-action" style="color:#b32d2e; border-color:#b32d2e;"
						onclick="return confirm('<?php esc_attr_e( 'Delete ALL log files? This cannot be undone.', 'astartha-dev-logger' ); ?>')">
						<?php esc_html_e( 'Delete All Logs', 'astartha-dev-logger' ); ?>
					</button>
				</form>
			</h1>

			<?php if ( 'deleted' === $devlog_msg ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Log file deleted.', 'astartha-dev-logger' ); ?></p>
				</div>
			<?php elseif ( 'all_deleted' === $devlog_msg ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'All log files deleted.', 'astartha-dev-logger' ); ?></p>
				</div>
			<?php endif; ?>

			<?php if ( empty( $files ) ) : ?>
				<p><?php esc_html_e( 'No log files found. Logs will appear here once devlog() is called.', 'astartha-dev-logger' ); ?></p>
			<?php else : ?>

			<div id="devlog-viewer">

				<div id="devlog-file-list">
					<ul>
					<?php foreach ( $files as $filepath ) :
						$fname     = basename( $filepath );
						$is_active = ( $fname === $current_filename );
						$size      = size_format( filesize( $filepath ), 1 );
						$url       = add_query_arg( 'file', rawurlencode( $fname ), $base_url );
					?>
						<li class="<?php echo esc_attr( $is_active ? 'active' : '' ); ?>">
							<a href="<?php echo esc_url( $url ); ?>">
								<span class="devlog-fname"><?php echo esc_html( $fname ); ?></span>
								<span class="devlog-fsize"><?php echo esc_html( $size ); ?></span>
							</a>
						</li>
					<?php endforeach; ?>
					</ul>
				</div>

				<div id="devlog-content-area">
					<?php if ( $current_filename ) : ?>
					<div class="devlog-toolbar">
						<strong><?php echo esc_html( $current_filename ); ?></strong>
						<span class="devlog-toolbar-actions">
							<a href="<?php echo esc_url( wp_nonce_url(
								add_query_arg( array( 'devlog_action' => 'download', 'file' => $current_filename ), $base_url ),
								'devlog_download_file'
							) ); ?>" class="button button-small">
								<?php esc_html_e( 'Download', 'astartha-dev-logger' ); ?>
							</a>
							<a href="<?php echo esc_url( wp_nonce_url(
								add_query_arg( array( 'devlog_action' => 'delete', 'file' => $current_filename ), $base_url ),
								'devlog_delete_file'
							) ); ?>" class="button button-small button-link-delete"
							   onclick="return confirm('<?php esc_attr_e( 'Delete this log file? This cannot be undone.', 'astartha-dev-logger' ); ?>')">
								<?php esc_html_e( 'Delete', 'astartha-dev-logger' ); ?>
							</a>
						</span>
					</div>
					<pre id="devlog-log-output"><?php echo wp_kses( self::colorize( $current_content ), array( 'span' => array( 'class' => true ) ) ); ?></pre>
					<?php else : ?>
					<p><?php esc_html_e( 'Select a file to view its contents.', 'astartha-dev-logger' ); ?></p>
					<?php endif; ?>
				</div>

			</div>

			<?php endif; ?>
		</div>

		<style>
		#devlog-viewer {
			display: flex;
			gap: 16px;
			margin-top: 16px;
			align-items: flex-start;
		}
		#devlog-file-list {
			width: 230px;
			flex-shrink: 0;
			background: #fff;
			border: 1px solid #ccd0d4;
			border-radius: 2px;
		}
		#devlog-file-list ul { margin: 0; padding: 0; list-style: none; }
		#devlog-file-list li a {
			display: flex;
			justify-content: space-between;
			align-items: baseline;
			padding: 8px 12px;
			text-decoration: none;
			color: #1d2327;
			border-bottom: 1px solid #f0f0f0;
			font-size: 12px;
			line-height: 1.5;
			gap: 6px;
		}
		#devlog-file-list li:last-child a { border-bottom: none; }
		#devlog-file-list li a:hover { background: #f6f7f7; }
		#devlog-file-list li.active a { background: #2271b1; color: #fff; }
		#devlog-file-list li.active a .devlog-fsize { color: #cce0f5; }
		.devlog-fname { word-break: break-all; }
		.devlog-fsize { color: #646970; flex-shrink: 0; font-size: 11px; }
		#devlog-content-area { flex: 1; min-width: 0; }
		.devlog-toolbar {
			display: flex;
			justify-content: space-between;
			align-items: center;
			background: #fff;
			border: 1px solid #ccd0d4;
			border-bottom: none;
			padding: 8px 12px;
			border-radius: 2px 2px 0 0;
		}
		.devlog-toolbar-actions { display: flex; gap: 6px; }
		#devlog-log-output {
			background: #1e1e1e;
			color: #d4d4d4;
			padding: 16px;
			font-family: Consolas, 'Courier New', monospace;
			font-size: 12px;
			line-height: 1.7;
			overflow: auto;
			max-height: 650px;
			white-space: pre-wrap;
			word-break: break-all;
			margin: 0;
			border: 1px solid #ccd0d4;
			border-radius: 0 0 2px 2px;
		}
		.devlog-sep      { color: #3c3c3c; }
		.devlog-debug    { color: #9cdcfe; }
		.devlog-info     { color: #4ec9b0; }
		.devlog-warning  { color: #dcdcaa; }
		.devlog-error    { color: #f44747; }
		.devlog-critical { color: #f44747; font-weight: bold; }
		</style>

		<script>
		document.addEventListener( 'DOMContentLoaded', function() {
			var pre = document.getElementById( 'devlog-log-output' );
			if ( pre ) { pre.scrollTop = pre.scrollHeight; }
		} );
		</script>
		<?php
	}

	/**
	 * Wraps each log line in a colored <span> based on its level tag.
	 */
	private static function colorize( $content ) {
		$lines  = explode( "\n", $content );
		$output = '';
		foreach ( $lines as $line ) {
			$escaped = esc_html( $line );
			if ( strpos( $line, '**************************' ) !== false ) {
				$output .= '<span class="devlog-sep">' . $escaped . '</span>';
			} elseif ( strpos( $line, '[CRITICAL]' ) !== false ) {
				$output .= '<span class="devlog-critical">' . $escaped . '</span>';
			} elseif ( strpos( $line, '[ERROR]' ) !== false ) {
				$output .= '<span class="devlog-error">' . $escaped . '</span>';
			} elseif ( strpos( $line, '[WARNING]' ) !== false ) {
				$output .= '<span class="devlog-warning">' . $escaped . '</span>';
			} elseif ( strpos( $line, '[INFO]' ) !== false ) {
				$output .= '<span class="devlog-info">' . $escaped . '</span>';
			} elseif ( strpos( $line, '[DEBUG]' ) !== false ) {
				$output .= '<span class="devlog-debug">' . $escaped . '</span>';
			} else {
				$output .= $escaped;
			}
			$output .= "\n";
		}
		return $output;
	}
}

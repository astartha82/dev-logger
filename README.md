# Dev Logger

Dev Logger is a lightweight WordPress debugging plugin that provides structured, readable logs directly inside WordPress admin.

It is designed for developers who want fast, safe, and convenient debugging without relying on server access or raw error_log files.

Allows quick and flexible logging to `.log` files inside the `wp-content/uploads/devlog-logs/` with convenient preview directly in WordPress admin.

Features:

• structured log output
• readable formatting for arrays and objects
• safe admin-only visibility in WordPress admin panel
• minimal configuration
• minimal performance impact

Log files are stored inside the WordPress uploads directory and intended for developer use.
Access is restricted through WordPress admin interface.

---

## Requirements

WordPress 5.0 or higher  
PHP 7.4 or higher


---

## Installation

1. Upload the plugin to /wp-content/plugins/dev-logger/
2. Activate the plugin through the WordPress admin
3. Use devlog() function in your code

---

## General usage

```php
devlog( $log, $level = 'debug', $logfile = false );
```
Parameters:

• $log (mixed)
Data to log. Can be string, array, object, or any variable.

• $level (string, optional)
Log severity level. Allowed values:
'debug', 'info', 'warning', 'error', 'critical'
Default: 'debug'

• $logfile (string|false, optional)
Custom logfile name without extension.
If false, the default logfile is used.

Log a variable :
```php
devlog($myvar);
```

Log just a line of text:
```php
devlog('Just a line of text');
```

Log into a custom file:
```php
$logfile = 'another-file';
devlog($myvar, 'info', $logfile);
```

This will create a file like:
`wp-content/uploads/devlog-logs/devlog_another-file_2024-02-06.log`

Log with severity level:
```php
devlog('Database connection failed', 'error');
```

---

## Options

You can configure the directory and filename prefix using plugin settings or filters:

Log directory: devlog-logs (default)

File prefix: devlog (default)

Auto-delete logs older than 30 days (default)

## Author

Developed by Lilith Zakharyan
https://github.com/astartha82

## License

This plugin is open-source and distributed under the terms of the [GNU General Public License v2.0](LICENSE.txt).


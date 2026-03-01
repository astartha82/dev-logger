# Astartha Dev Logger

Astartha Dev Logger is a lightweight developer-friendly logging and debugging plugin for WordPress. It provides structured, readable logs stored in files and a convenient viewer inside the WordPress admin.

It is designed for developers who want fast, safe, and flexible debugging without relying on server access or raw `error_log` files.

Logs are stored in:

`wp-content/uploads/devlog-logs/`

and can be viewed, downloaded, and managed directly from the WordPress admin.

---

## Basic Usage

General function signature:

```php
devlog( $log, $level = 'debug', $logfile = false );
```

### Parameters

**`$log` (mixed)**  
Variable or message to log.

**`$level` (string, optional)**  
Log severity level. Allowed values: `debug`, `info`, `warning`, `error`, `critical`.  
Default: `debug`.

**`$logfile` (string|false, optional)**  
Custom log file name without extension.  
If `false`, the default logfile is used.

---

## Examples

Log any variable:

```php
devlog( $myVar );
```

Example output:

```text
[14:32:10] [DEBUG] my-plugin.php:42 | $myVar: some value
```

Log with severity level:

```php
devlog( $order_id, 'error' );
```

Log plain message:

```php
devlog( 'Payment gateway unreachable' );
```

Log to custom file:

```php
devlog( $order_id, 'error', 'payments' );
```

Creates file:

```text
wp-content/uploads/devlog-logs/devlog_payments_2024-02-06.log
```

---

## Author

Lilith Zakharyan  
https://github.com/astartha82

## License

GPL v2 or later  
https://www.gnu.org/licenses/gpl-2.0.html

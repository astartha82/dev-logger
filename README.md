# Simple WP Logger

A lightweight developer-friendly logger for WordPress.  
Allows quick and flexible logging to `.log` files inside the `wp-content/uploads/swpl-logs/` directory.

---

## 🔧 Usage

Log a simple message and a variable:

```php
_log('My log title', $myvar);
```
Log just a line of text:
```
_log('Just log this line of text');
```
Log multiple variables at once:
```
_log('Several variables', array($var1, $var2, $var3, ...));
```
Log into a custom file:
```
$logfile = 'another-file';
_log('Logging to another file', $myvar, $logfile);
```
This will create a file like:
`wp-content/uploads/swpl-logs/swpl_another-file_2024-02-06.log`

## ⚙️ Options

You can configure the directory and filename prefix using plugin settings or filters:

Directory: swpl-logs (default)

Filename prefix: swpl (default)

## 🧑💻 Author

Developed by Lilith Zakharyan
https://github.com/astartha82

## 📄 License

This plugin is open-source and distributed under the terms of the [GNU General Public License v2.0](LICENSE.txt).


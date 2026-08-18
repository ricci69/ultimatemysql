# ultimatemysql
MySQL database access wrapper compatible with PHP 8.1+

Based on the excellent work of Jeff Williams
https://www.phpclasses.org/package/3698-PHP-MySQL-database-access-wrapper.html

### Why this repository
Despite it is being used by fortune 500 companies [[source](https://www.phpclasses.org/discuss/package/3698/thread/72/)], the script isn't update since 16/08/2013 (almost 10 years!), so i thinked to create this repository with the aim of making it compatible with PHP 8, and being able to use it for another 10 years (i hope!).

> **⚠️ IMPORTANT: PHP Version Requirement**
> **Version 5.0+ requires PHP 8.1 or higher** (uses `readonly` properties, `never` return type, union types, `mixed` type hint).
> 
> **If you are on PHP 7.x (7.0 - 7.4) or PHP 8.0**, you **MUST** use version **4.6** (the last 4.x release).
> ```bash
> composer require ricci69/ultimatemysql:^4.6
> ```
> Version 4.6 is the last compatible with PHP 7.0+.

## How to get started
It's a very simple database access wrapper, well documented and safe against SQL injection hacks!

You can start with only 3 lines!

### PHP Library
```php
<?php
include("mysql.class.php");
$db = new MySQL(true, "testdb", "localhost", "root", "password"); // Requires PHP 8.1+
$res = $db->QueryArray("SELECT * FROM Test");
?>
```

### Composer package
...OR if you want to include it as a composer package, just type:
```shell
composer require ricci69/ultimatemysql # Installs v5.x (PHP 8.1+)
```
and after use the following PHP code
```php
<?php
require 'vendor/autoload.php';
$db = new MySQL(true, "testdb", "localhost", "root", "password"); // Requires PHP 8.1+
$res = $db->QueryArray("SELECT * FROM Test");
?>
```

### Debug mode
The script looks for a file called ```.debugmysql``` (within the root directory or within the composer's vendor / module folder) and, if found, enters debug mode.

When debug mode is active, it writes all SQL queries executed inside the ```.debugmysql``` file.

## How it work and examples
Check the "examples" directory to learn how it works, or read the very good documentation on https://ricci69.github.io/ultimatemysql


## Contributions
Feel free to contribute to this project adding more feature or fixing issues, but before submitting a pull request, make sure your code passes all unit tests (refer to the [/tests/coverage.md](/tests/coverage.md) document)

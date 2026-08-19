<?php
/**
 * Bootstrap configuration for all examples.
 * Copy this file to bootstrap.local.php and edit credentials there for security.
 */

// Database Credentials - CHANGE THESE
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_NAME', 'testdb');
define('DB_CHARSET', 'utf8mb4');
define('DB_PERSISTENT', false);

// Optional: SSL / Timeout for Open() demo
define('DB_SSL_OPTIONS', null); // e.g. ['ca' => '/path/ca.pem', 'cert' => '/path/client-cert.pem', 'key' => '/path/client-key.pem']
define('DB_CONNECT_TIMEOUT', 5);

// Safety Constants (can be overridden before including mysql.class.php)
// define('MYSQL_MAX_BUFFERED_ROWS', 100); // Lower limit for testing memory safety
// define('MYSQL_DEBUG_ANONIMIZATION', true); // Enable SQL anonymization in debug logs

// Error Reporting for development
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
ini_set('display_errors', '1');
ini_set('html_errors', '1');

// Helper to print section headers
function section(string $title): void {
    echo "<hr style='margin: 2rem 0; border-color: #ccc;'>";
    echo "<h2 style='color: #333; border-bottom: 2px solid #007bff; padding-bottom: 0.3rem;'>{$title}</h2>";
}

function dump(mixed $var, string $label = ''): void {
    if ($label) echo "<strong>{$label}:</strong><br>";
    echo "<pre style='background:#f8f9fa; padding:10px; border:1px solid #dee2e6; border-radius:4px; overflow:auto;'>";
    var_dump($var);
    echo "</pre>";
}

function success(string $msg): void { echo "<div style='color: #155724; background: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 4px; margin: 10px 0;'>✅ {$msg}</div>"; }
function fail(string $msg): void { echo "<div style='color: #721c24; background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 4px; margin: 10px 0;'>❌ {$msg}</div>"; }
function info(string $msg): void { echo "<div style='color: #0c5460; background: #d1ecf1; border: 1px solid #bee5eb; padding: 10px; border-radius: 4px; margin: 10px 0;'>ℹ️ {$msg}</div>"; }

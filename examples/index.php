<?php
/**
 * Examples Dashboard
 */
$files = [
    '01_basic_connection.php' => 'Basic Connection & Lifecycle',
    '02_core_queries.php' => 'Core Queries & Result Navigation',
    '03_result_export_metadata.php' => 'Export (HTML/JSON/XML) & Metadata',
    '04_write_operations.php' => 'Write Ops (Insert, Update, Delete, Upsert)',
    '05_transactions.php' => 'Transactions & Exception Handling',
    '06_prepared_statements.php' => 'Prepared Statements (v5.0 Core)',
    '07_sql_builders_autoescape.php' => 'SQL Builders & Auto-Escape',
    '08_advanced_features.php' => 'Advanced: Streaming, Reconnect, Timers, Types',
    '09_security_memory_safety.php' => 'Security & Memory Safety Guards',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Ultimate MySQL v5.0 - Examples</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; max-width: 900px; margin: 2rem auto; padding: 0 1rem; line-height: 1.6; }
        h1 { border-bottom: 2px solid #007bff; padding-bottom: 0.5rem; }
        ul { list-style: none; padding: 0; }
        li { margin: 0.5rem 0; }
        a { display: block; padding: 1rem; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px; text-decoration: none; color: #333; transition: all 0.2s; }
        a:hover { background: #e9ecef; border-color: #007bff; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .badge { background: #007bff; color: white; padding: 0.2rem 0.5rem; border-radius: 3px; font-size: 0.8em; margin-right: 0.5rem; }
        .new { background: #28a745; }
    </style>
</head>
<body>
    <h1>Ultimate MySQL v5.0 Examples</h1>
    <?php require_once __DIR__ . '/navbar.php'; ?>
    <p>PHP 8.1+ Required. Ensure <code>bootstrap.php</code> is configured with your database credentials.</p>
    <ul>
        <?php foreach ($files as $file => $title): ?>
            <li>
                <a href="<?php echo $file; ?>">
                    <?php if (strpos($file, '06_') === 0 || strpos($file, '09_') === 0): ?>
                        <span class="badge new">v5.0 Feature</span>
                    <?php endif; ?>
                    <strong><?php echo $title; ?></strong>
                    <br><small><?php echo $file; ?></small>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
    <hr>
    <p><strong>Quick Test:</strong> Run <code>php 01_basic_connection.php</code> from CLI or open in browser.</p>
</body>
</html>

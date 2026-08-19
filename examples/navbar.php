<?php
/**
 * Navbar di navigazione condivisa per tutti gli esempi.
 */
$exampleList = [
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

$currentFile = basename($_SERVER['SCRIPT_FILENAME']);
$keys = array_keys($exampleList);
$currentIndex = array_search($currentFile, $keys);
$prevFile = ($currentIndex !== false && $currentIndex > 0) ? $keys[$currentIndex - 1] : null;
$nextFile = ($currentIndex !== false && $currentIndex < count($keys) - 1) ? $keys[$currentIndex + 1] : null;
?>
<nav style="background:#f8f9fa; border:1px solid #dee2e6; border-radius:4px; padding:1rem; margin-bottom:2rem; position:sticky; top:1rem; z-index:100; font-family: system-ui, sans-serif;">
    <div style="display:flex; flex-wrap:wrap; gap:0.4rem; justify-content:center; align-items:center; margin-bottom:0.5rem;">
        <strong style="margin-right:0.5rem; color:#333;">📚 Esempi:</strong>
        <?php foreach ($exampleList as $file => $title): ?>
            <?php $isActive = ($file === $currentFile); ?>
            <a href="<?= $file ?>" 
               style="padding:0.3rem 0.6rem; border-radius:3px; text-decoration:none; font-size:0.85rem; font-weight:500; 
                      <?= $isActive ? 'background:#0d6efd; color:white; border:1px solid #0d6efd;' : 'background:white; color:#333; border:1px solid #ccc;' ?>">
                <?= basename($file, '.php') ?>
            </a>
        <?php endforeach; ?>
    </div>
    <hr style="margin: 0.5rem 0; border-color: #dee2e6;">
    <div style="display:flex; justify-content:space-between; align-items:center; font-size:0.9rem; color:#495057;">
        <a href="<?= $prevFile ?? '#' ?>" style="text-decoration:none; color:<?= $prevFile ? '#0d6efd' : '#adb5bd' ?>; pointer-events:<?= $prevFile ? 'auto' : 'none' ?>;">« Precedente</a>
        <strong style="flex:1; text-align:center;"><?= $exampleList[$currentFile] ?? '' ?></strong>
        <a href="<?= $nextFile ?? '#' ?>" style="text-decoration:none; color:<?= $nextFile ? '#0d6efd' : '#adb5bd' ?>; pointer-events:<?= $nextFile ? 'auto' : 'none' ?>; text-align:right;">Successivo »</a>
    </div>
</nav>

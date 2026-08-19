<?php
/**
 * Example: Static SQL Builders & Auto-Escape
 * Covers: BuildSQLSelect, BuildSQLInsert, BuildSQLUpdate, BuildSQLDelete, BuildSQLWhereClause,
 *         EscapeIdentifier, SetAutoEscapeValues, SetGlobalAutoEscapeValues, detectSqlValueType
 */
error_reporting(E_ALL & ~E_DEPRECATED);
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/navbar.php';
require_once __DIR__ . '/../mysql.class.php';

section("1. EscapeIdentifier (Security)");
try {
    echo "Valid: " . MySQL::EscapeIdentifier("my_table") . "<br>";
    echo "Valid with spaces: " . MySQL::EscapeIdentifier("my column") . "<br>"; // Throws
} catch (InvalidArgumentException $e) {
    fail("Caught expected error for 'my column': " . $e->getMessage());
}
try {
    MySQL::EscapeIdentifier("table; DROP TABLE users;--");
} catch (InvalidArgumentException $e) {
    success("Blocked SQL Injection attempt in identifier: " . $e->getMessage());
}

section("2. BuildSQLSelect (Complex)");
$where = [
    'age >' => 18,
    'status' => 'active',
    'role IN' => ['admin', 'editor'],
    'deleted_at' => null, // Becomes IS NULL
    '_raw' => 'OR (created_at > "2023-01-01")' // Raw fragment (use with caution)
];
$sql = MySQL::BuildSQLSelect('users', $where, ['id', 'username', 'email'], ['username' => 'ASC'], true, 10, 0);
dump($sql, "BuildSQLSelect with operators, IN, NULL, _raw");

section("3. BuildSQLInsert / Update / Delete / WhereClause");
// Fix: Converti DateTime esplicitamente per sicurezza con autoEscape
$vals = [
    'email' => 'test@test.com', 
    'login_count' => 5, 
    'last_login' => MySQL::SQLValue(new DateTime(), MySQL::SQLVALUE_DATETIME) // Conversione esplicita
];
dump(MySQL::BuildSQLInsert('users', $vals, true), "Insert (AutoEscape)");

dump(MySQL::BuildSQLUpdate('users', ['login_count' => 6], ['id' => 1]), "Update");
dump(MySQL::BuildSQLDelete('users', ['id' => 1]), "Delete");
dump(MySQL::BuildSQLWhereClause($where), "WhereClause only");

section("4. Auto-Escape Values (Instance & Global)");
// Instance mode
$db = new MySQL(false);
$db->SetAutoEscapeValues(true);
$vals = ['name' => "O'Reilly", 'age' => 30]; // Raw PHP values
$sql = MySQL::BuildSQLInsert('users', $vals, true); // true = autoEscape
dump($sql, "Instance AutoEscape: Handles quotes & types automatically");

// Global mode
MySQL::SetGlobalAutoEscapeValues(true);
$db2 = new MySQL(false); // New instance inherits global
$sql2 = MySQL::BuildSQLUpdate('users', ['name' => "Mc'Donald"], ['id' => 2], true);
dump($sql2, "Global AutoEscape: Inherited by new instance");
MySQL::SetGlobalAutoEscapeValues(false); // Reset global

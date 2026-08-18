# Test Suite Coverage - Ultimate MySQL v5.0+

## 🎯 Requirements
- **PHP 8.1+** (PHP 7.x / 8.0 not supported - see v4.6 for legacy)
- `mysqli` extension enabled
- Accessible MySQL/MariaDB server (default: `127.0.0.1:3306`, user `root`, pass `root`, db `testdb`)

## 🚀 How to Run Tests

### 1. Database Setup (One-time)
```bash
# Create the test DB and tables
mysql -uroot -p < tests/db.sql
```

### 2. Install Dev Dependencies
```bash
composer install --dev
```

### 3. Run Tests (TestDox for readable output)
```bash
# All tests
./vendor/bin/phpunit --testdox tests

# Specific test suite (e.g., Prepared Statements)
./vendor/bin/phpunit --testdox tests/PreparedStatementsTest.php

# With Code Coverage (requires pcov/xdebug enabled)
./vendor/bin/phpunit --coverage-html coverage/html tests
```

### 4. Static Analysis (Optional but recommended)
```bash
# PHPStan Level 5
./vendor/bin/phpstan analyse mysql.class.php --level=5

# Psalm
./vendor/bin/psalm --no-cache
```

---

## 📊 Functional Coverage: **65/65 Public/Protected Methods** ✅

| Category | Test File | Key Methods Covered | Notes |
| :--- | :--- | :--- | :--- |
| **Core & Connection** | `ConnectionTest.php` | `__construct`, `Open`, `Close`, `IsConnected`, `SelectDatabase`, `SetAutoReconnect`, `ensureConnected` | Tests auto-reconnect, SSL, timeout, persistent connections. |
| **Query & Result Set** | `QueryTest.php` | `Query`, `QueryArray`, `QuerySingleRow`, `QuerySingleRowArray`, `QuerySingleValue`, `QueryTimed`, `SelectRows`, `SelectTable`, `HasRecords`, `RowCount`, `Row`, `RowArray`, `Records`, `RecordsArray`, `Release`, `MoveFirst`, `MoveLast`, `Seek`, `SeekPosition`, `BeginningOfSeek`, `EndOfSeek`, `GetLastSQL`, `GetLastInsertID`, `AutoInsertUpdate` | Includes multi-statement rejection tests. |
| **Prepared Statements** | `PreparedStatementsTest.php` | `Prepare`, `BindParam`, `BindParams`, `Execute`, `Fetch`, `FetchAll`, `CloseStatement`, `PreparedRowCount` | **New in v5.0**. Tests no-mysqlnd fallback (unbuffered). |
| **Write Operations** | `WriteTest.php` | `InsertRow`, `UpdateRows`, `DeleteRows`, `TruncateTable`, `TransactionBegin`, `TransactionEnd`, `TransactionRollback`, `IsInTransaction`, `GetTransactionDepth` | Tests transactions, rollback, mass-update/delete protection. |
| **SQL Builders (Static)** | `BuildTest.php` | `BuildSQLSelect`, `BuildSQLInsert`, `BuildSQLUpdate`, `BuildSQLDelete`, `BuildSQLWhereClause`, `BuildSQLSetClause`, `BuildSQLColumns`, `EscapeIdentifier` | Tests auto-escape, operators (`IN`, `LIKE`, `IS NULL`), `_raw`. |
| **Auto-Escape** | `AutoEscapeTest.php` | `SetAutoEscapeValues`, `SetGlobalAutoEscapeValues`, `getAutoEscapeMode` | Tests global/instance modes, SQLValue integration. |
| **Security** | `SecurityIdentifiersTest.php` | `EscapeIdentifier` | Tests injection via backticks, spaces, `;`, `--`, quotes, slashes. |
| **Auto-Reconnect** | `AutoReconnectTest.php` | `SetAutoReconnect`, `ensureConnected` | Tests reconnect outside transaction, block inside transaction. |
| **Connection Resilience** | `ConnectionResilienceTest.php` | `Query` (after connection kill) | Simulates `mysqladmin kill` / timeout. |
| **Column Metadata** | `ColumnTest.php` | `GetColumnNames`, `GetColumnCount`, `GetColumnDataType`, `GetColumnDataTypeName`, `GetColumnLength`, `GetColumnID`, `GetColumnName`, `GetColumnComments`, `GetTables` | Tests on live result sets and `SHOW COLUMNS`. |
| **Export** | `ExportTest.php` | `GetHTML`, `GetJSON`, `GetXML` | Tests safety limit `MYSQL_MAX_BUFFERED_ROWS`. |
| **Timer** | `TimerTest.php` | `TimerStart`, `TimerStop`, `TimerDuration`, `QueryTimed` | |
| **Values & Helpers** | `ValueTest.php` | `SQLValue`, `SQLFix`, `SQLBooleanValue`, `GetBooleanValue`, `IsDate` (deprecated) | Tests types: `TEXT`, `NUMBER`, `DATE`, `DATETIME`, `TIME`, `BOOLEAN`, `BIT`, `YN`, `TF`. |
| **Error Handling** | `ErrorTest.php` | `Error`, `ErrorNumber`, `SetThrowExceptions`, `Kill` | Tests exception throwing, error codes. |

---

## ⚙️ CI Configuration (GitHub Actions)

The file `.github/workflows/tests.yml` runs a **Matrix Strategy**:

| PHP Version | MySQL Version | Notes |
| :--- | :--- | :--- |
| **8.1** | 8.0, 8.4 | Minimum Supported |
| **8.2** | 8.0, 8.4 | Active Support |
| **8.3** | 8.0, 8.4 | **Coverage Job** (pcov) |
| **8.4** | 8.0, 8.4 | Latest Stable |

**Total Jobs per run: 8** (4 PHP × 2 MySQL).
*No tests on PHP 7.3, 7.4, 8.0, 8.5 (nightly).*

---

## 🛡️ Safety Limits & Memory Testing

Tests explicitly verify **Memory Safety Guards** (`MYSQL_MAX_BUFFERED_ROWS = 50000`):

| Method | Expected Behavior if `RowCount > 50000` |
| :--- | :--- |
| `RecordsArray()` | `false` + `SetError("...exceeds safety limit...")` |
| `GetJSON()` | `'null'` + Error log |
| `GetHTML()` | `false` + Error log |
| `GetXML()` | XML with attribute `error="Result set too large..."` |
| `FetchAll()` | `false` + Error log |
| `RowArray()` / `Fetch()` loop | **OK** (Streaming, no limit) |

---

## 🐛 Known Limitations / Skipped Tests

| Feature | Status | Reason |
| :--- | :--- | :--- |
| `MoveFirst()` / `MoveLast()` / `Seek()` / `RowCount()` on **Prepared Statements unbuffered (no mysqlnd)** | **Tested: Throws Error** | Architectural limitation of `mysqli` without `mysqlnd`. Library guides user to `Fetch()` loop or `COUNT(*)`. |
| `mysqli_ping()` | **Removed** | Deprecated in PHP 8.4. Replaced by `SELECT 1` in `ensureConnected()`. |
| `IsDate()` | **Deprecated** | Test only verifies `E_USER_DEPRECATED` trigger. |
| `SQLUnfix()` | **Removed** | Not present in v5.0 codebase. |

---

## 📁 `tests/` Directory Structure

```text
tests/
├── bootstrap.php          # Autoload + Test constants (DB credentials)
├── db.sql                 # Schema + Initial data (testdb, test_table, test_query)
├── ConnectionTest.php
├── QueryTest.php
├── PreparedStatementsTest.php
├── WriteTest.php
├── BuildTest.php
├── AutoEscapeTest.php
├── SecurityIdentifiersTest.php
├── AutoReconnectTest.php
├── ConnectionResilienceTest.php
├── ColumnTest.php
├── ExportTest.php
├── TimerTest.php
├── ValueTest.php
├── ErrorTest.php
└── coverage.md            # This file
```

---

## ✅ Pre-Release Checklist (v5.0.0)

- [ ] `composer validate --strict` passes
- [ ] `./vendor/bin/phpunit --testdox tests` → **0 Failures, 0 Errors, 0 Skipped** (on PHP 8.1+)
- [ ] `./vendor/bin/phpstan analyse mysql.class.php --level=5` passes
- [ ] `./vendor/bin/psalm --no-cache` passes
- [ ] GitHub Actions Matrix (8 jobs) **All Green**
- [ ] `MYSQL_MAX_BUFFERED_ROWS` manually tested with table > 50k rows (optional)
- [ ] Tag `v5.0.0` pushed → Packagist updated
